<?php
// app/Jobs/RenewSubscriptionsJob.php

namespace App\Jobs;

use App\Models\Subscription;
use App\Telegram\Traits\CanUsePayme;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RenewSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, CanUsePayme;

    public function handle(): void
    {
        $now = Carbon::now();
        $expirationThreshold = $now->copy()->addHours(12);

        Subscription::query()->where('status', 1)
            ->whereBetween('expires_at', [$now, $expirationThreshold])
            ->chunk(100, function ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    /** @var Subscription $subscription */
                    $user = $subscription->client;
                    if (!$user) {
                        continue;
                    }

                    // Set the language for the user
                    app()->setLocale($user->lang ?? 'uz');

                    $verifiedCard = $user->cards()->where('verified', true)->latest()->first();
                    if (!$verifiedCard) {
                        Telegraph::chat($user->chat_id)
                            ->message(__('telegram.subscription_renewal_no_card', ['name' => $user->first_name . ' ' . $user->last_name]))
                            ->send();
                        continue;
                    }

                    try {
                        $this->callRecurrentPay($subscription->plan, $subscription->client);
                        Telegraph::chat($user->chat_id)
                            ->sticker("CAACAgIAAxkBAAExKjRnl0Nr7-7-U-Ita4YDc764z65TRwACiQADFkJrCkbL2losgrCONgQ")
                            ->send();
                        Telegraph::chat($user->chat_id)
                            ->message(__('telegram.subscription_renewal_success', ['name' => $user->first_name . ' ' . $user->last_name]))
                            ->send();
                    } catch (Exception) {
                        Telegraph::chat($user->chat_id)
                            ->message(__('telegram.subscription_renewal_error', ['name' => $user->first_name . ' ' . $user->last_name]))
                            ->send();
                    }
                }
            });
    }
}
