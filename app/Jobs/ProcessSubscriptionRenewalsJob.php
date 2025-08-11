<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Telegram\Services\HandleChannel;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionRenewalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $this->processExpiringSubscriptions();
        $this->processExpiredSubscriptions();
    }

    /**
     * Process subscriptions that are expiring in 3 days or less
     */
    private function processExpiringSubscriptions(): void
    {
        Subscription::query()
            ->where('status', true)
            ->whereBetween('expires_at', [
                now()->startOfDay(),
                now()->addDays(3)->endOfDay()
            ])
            ->whereHas('client.cards', function ($query) {
                $query->where('verified', true)->where('is_main', true);
            })
            ->with(['client', 'plan'])
            ->chunk(100, function ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    try {
                        $this->attemptRenewal($subscription);
                    } catch (Exception $e) {
                        Log::error('Subscription renewal failed', [
                            'subscription_id' => $subscription->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });
    }

    /**
     * Process expired subscriptions
     */
    private function processExpiredSubscriptions(): void
    {
        Subscription::query()
            ->where('status', true)
            ->where('expires_at', '<', now())
            ->with(['client', 'plan'])
            ->chunk(100, function ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    // Check if it's an expired free plan that expires today
                    if ($subscription->plan->price === 0 && 
                        $subscription->expires_at->isToday() &&
                        $subscription->client->cards()->where('verified', true)->exists()) {
                        $this->attemptFreeToOneMonthRenewal($subscription);
                    } else {
                        $this->handleExpiredSubscription($subscription);
                    }
                }
            });
    }

    /**
     * Attempt to renew expired free plan to one-month paid plan
     */
    private function attemptFreeToOneMonthRenewal(Subscription $subscription): void
    {
        $client = $subscription->client;
        
        // Set locale
        app()->setLocale($client->lang ?? 'uz');
        
        // Get one-month plan
        $oneMonthPlan = \App\Models\Plan::where('name', 'one-month')->first();
        if (!$oneMonthPlan) {
            Log::error('One-month plan not found for free plan renewal');
            return;
        }
        
        // Get verified cards
        $cards = $client->cards()
            ->where('verified', true)
            ->orderBy('is_main', 'desc')
            ->get();
            
        $renewed = false;
        $lastError = null;
        
        foreach ($cards as $card) {
            try {
                // Create a temporary subscription with one-month plan for renewal
                $tempSubscription = new Subscription();
                $tempSubscription->client_id = $client->id;
                $tempSubscription->plan_id = $oneMonthPlan->id;
                $tempSubscription->expires_at = $subscription->expires_at;
                
                // Attempt payment with this card
                $service = app(SubscriptionService::class);
                $newSubscription = $service->renewSubscription($tempSubscription, $card);
                
                if ($newSubscription) {
                    $renewed = true;
                    // Mark the new subscription as a renewal from free plan
                    $newSubscription->update([
                        'previous_subscription_id' => $subscription->id,
                        'is_renewal' => true,
                    ]);
                    
                    // Deactivate old free subscription
                    $subscription->update(['status' => false]);
                    
                    // Notify user about automatic upgrade
                    Telegraph::chat($client->chat_id)
                        ->message(__('telegram.free_plan_auto_renewed', [
                            'plan' => $oneMonthPlan->name,
                            'price' => $oneMonthPlan->price / 100,
                            'expires_at' => $newSubscription->expires_at->format('d.m.Y')
                        ]))
                        ->send();
                    break;
                }
            } catch (Exception $e) {
                $lastError = $e->getMessage();
                Log::warning('Free plan renewal payment failed', [
                    'card_id' => $card->id,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        if (!$renewed) {
            // If renewal failed, handle as expired subscription
            $this->handleExpiredSubscription($subscription);
        }
    }

    /**
     * Attempt to renew a subscription
     */
    private function attemptRenewal(Subscription $subscription): void
    {
        $client = $subscription->client;
        $plan = $subscription->plan;

        // Set locale
        app()->setLocale($client->lang ?? 'uz');

//        // Skip if already attempted today
//        if ($subscription->last_payment_attempt &&
//            $subscription->last_payment_attempt->isToday()) {
//            return;
//        }

        // Get all verified cards
        $cards = $client->cards()
            ->where('verified', true)
            ->orderBy('is_main', 'desc')
            ->get();

        $renewed = false;
        $lastError = null;

        foreach ($cards as $card) {
            try {
                // Attempt payment with this card
                $service = app(SubscriptionService::class);
                $newSubscription = $service->renewSubscription($subscription, $card);

                if ($newSubscription) {
                    $renewed = true;
                    $this->notifySuccessfulRenewal($client, $newSubscription);
                    break;
                }
            } catch (Exception $e) {
                $lastError = $e->getMessage();
                Log::warning('Card payment failed', [
                    'card_id' => $card->id,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (!$renewed) {
            // Update retry count and error
            $subscription->increment('payment_retry_count');
            $subscription->update([
                'last_payment_attempt' => now(),
                'last_payment_error' => $lastError
            ]);

            $this->notifyFailedRenewal($client, $subscription);
        }
    }

    /**
     * Handle expired subscription
     */
    private function handleExpiredSubscription(Subscription $subscription): void
    {
        DB::transaction(function () use ($subscription) {
            // Deactivate subscription
            $subscription->update(['status' => false]);

            // Remove from channel
            $client = $subscription->client;
            app()->setLocale($client->lang ?? 'uz');

            try {
                $handleChannel = new HandleChannel($client);
                if ($handleChannel->getChannelUser() !== 'unknown') {
                    $handleChannel->kickUser();
                }
            } catch (Exception $e) {
                Log::error('Failed to kick user from channel', [
                    'client_id' => $client->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Notify user
            $this->notifyExpiredSubscription($client, $subscription);
        });
    }

    /**
     * Notify user of successful renewal
     */
    private function notifySuccessfulRenewal($client, $newSubscription): void
    {
        Telegraph::chat($client->chat_id)
            ->message(__('telegram.subscription_renewed_success', [
                'plan' => $newSubscription->plan->name,
                'expires_at' => $newSubscription->expires_at->format('d.m.Y')
            ]))
            ->send();
    }

    /**
     * Notify user of failed renewal
     */
    private function notifyFailedRenewal($client, $subscription): void
    {
        $daysLeft = max(0, $subscription->expires_at->diffInDays(now()));

        Telegraph::chat($client->chat_id)
            ->message(__('telegram.subscription_renewal_failed', [
                'days_left' => $daysLeft
            ]))
            ->keyboard(\DefStudio\Telegraph\Keyboard\Keyboard::make()->buttons([
                \DefStudio\Telegraph\Keyboard\Button::make(__('telegram.pay_now_button'))
                    ->action('renewSubscription')
                    ->param('subscription_id', $subscription->id)
            ]))
            ->send();
    }

    /**
     * Notify user of expired subscription
     */
    private function notifyExpiredSubscription($client, $subscription): void
    {
        Telegraph::chat($client->chat_id)
            ->message(__('telegram.subscription_expired_kicked'))
            ->keyboard(\DefStudio\Telegraph\Keyboard\Keyboard::make()->buttons([
                \DefStudio\Telegraph\Keyboard\Button::make(__('telegram.view_plans_button'))
                    ->action('showPlans')
            ]))
            ->send();
    }
}
