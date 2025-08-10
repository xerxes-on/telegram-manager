<?php
// app/Jobs/RenewSubscriptionsJob.php

namespace App\Jobs;

use App\Models\Subscription;
use App\Telegram\Services\HandleChannel;
use App\Telegram\Traits\CanUsePayme;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RenewSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, CanUsePayme;

    public function handle(): void
    {
        $now = Carbon::now();
        $expirationThreshold = $now->copy()->addHours(12);
        $maxRetries = config('services.payment.max_retries', 3);

        Subscription::query()->where('status', 1)
            ->whereBetween('expires_at', [$now, $expirationThreshold])
            ->where('payment_retry_count', '<', $maxRetries)
            ->chunk(100, function ($subscriptions) use ($maxRetries) {
                foreach ($subscriptions as $subscription) {
                    /** @var Subscription $subscription */
                    $user = $subscription->client;
                    if (!$user) {
                        continue;
                    }

                    // Set the language for the user
                    app()->setLocale($user->lang ?? 'uz');

                    $verifiedCard = $user->cards()->where(['verified' => true, 'is_main' => true])->first();
                    if (!$verifiedCard) {
                        Telegraph::chat($user->chat_id)
                            ->message(__('telegram.subscription_renewal_no_card', ['name' => $user->first_name]))
                            ->send();
                        continue;
                    }

                    try {
                        // Payme recurrent charge expects (Client, Plan)
                        $this->callRecurrentPay($subscription->client, $subscription->plan);
                        
                        // Reset retry count on successful payment
                        $subscription->update([
                            'payment_retry_count' => 0,
                            'last_payment_attempt' => now(),
                            'last_payment_error' => null
                        ]);
                        
                        Telegraph::chat($user->chat_id)
                            ->sticker("CAACAgIAAxkBAAExKjRnl0Nr7-7-U-Ita4YDc764z65TRwACiQADFkJrCkbL2losgrCONgQ")
                            ->send();
                        Telegraph::chat($user->chat_id)
                            ->message(__('telegram.subscription_renewal_success', ['name' => $user->first_name]))
                            ->send();
                    } catch (Exception $e) {
                        // Increment retry count
                        $subscription->increment('payment_retry_count');
                        $subscription->update([
                            'last_payment_attempt' => now(),
                            'last_payment_error' => $e->getMessage()
                        ]);
                        
                        Log::error('Subscription renewal failed', [
                            'subscription_id' => $subscription->id,
                            'client_id' => $user->id,
                            'error' => $e->getMessage(),
                            'retry_count' => $subscription->payment_retry_count
                        ]);
                        
                        // If max retries reached, deactivate subscription and kick from channel
                        if ($subscription->payment_retry_count >= $maxRetries) {
                            $subscription->update(['status' => 0]);
                            
                            $handleChannel = new HandleChannel($user);
                            if ($handleChannel->getChannelUser() !== 'unknown') {
                                $handleChannel->kickUser();
                            }
                            
                            Telegraph::chat($user->chat_id)
                                ->message(__('telegram.subscription_renewal_max_retries_reached', [
                                    'name' => $user->first_name,
                                    'retry_count' => $maxRetries
                                ]))
                                ->send();
                        } else {
                            Telegraph::chat($user->chat_id)
                                ->message(__('telegram.subscription_renewal_error', ['name' => $user->first_name]))
                                ->send();
                        }
                    }
                }
            });
    }
}
