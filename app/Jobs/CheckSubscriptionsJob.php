<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Telegram\Services\HandleChannel;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expiredCount = 0;
        $kickedCount = 0;
        
        Log::info('Starting subscription check job');
        
        Subscription::query()
            ->where('status', 1)
            ->where('expires_at', '<', Carbon::now())
            ->chunk(100, function ($subscriptions) use (&$expiredCount, &$kickedCount) {
                foreach ($subscriptions as $subscription) {
                    /** @var Subscription $subscription */
                    $user = $subscription->client;
                    
                    if (!$user) {
                        Log::warning('Subscription without client found', ['subscription_id' => $subscription->id]);
                        continue;
                    }
                    
                    app()->setLocale($user->lang ?? 'uz');
                    
                    // Mark subscription as expired
                    $subscription->update(['status' => 0]);
                    $expiredCount++;
                    
                    Log::info('Subscription expired', [
                        'subscription_id' => $subscription->id,
                        'client_id' => $user->id,
                        'client_name' => $user->first_name,
                        'expired_at' => $subscription->expires_at
                    ]);
                    
                    // Remove user from channel
                    try {
                        $handleChannel = new HandleChannel($user);
                        if ($handleChannel->getChannelUser() !== 'unknown') {
                            $handleChannel->kickUser();
                            $kickedCount++;
                            
                            // Notify user they were removed
                            Telegraph::chat($user->chat_id)
                                ->message(__('telegram.user_kicked'))
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to kick user from channel', [
                            'client_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });
            
        Log::info('Subscription check job completed', [
            'expired_subscriptions' => $expiredCount,
            'users_kicked' => $kickedCount
        ]);
    }
}
