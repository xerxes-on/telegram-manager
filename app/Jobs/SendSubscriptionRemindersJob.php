<?php

namespace App\Jobs;

use App\Models\Subscription;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSubscriptionRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->sendFirstReminder();  // 3 days before
        $this->sendSecondReminder(); // 2 days before
        $this->sendFinalReminder();  // 1 day before
    }

    /**
     * Send first reminder (3 days before expiry)
     */
    private function sendFirstReminder(): void
    {
        $subscriptions = Subscription::query()
            ->where('status', true)
            ->whereBetween('expires_at', [
                now()->addDays(3)->startOfDay(),
                now()->addDays(3)->endOfDay()
            ])
            ->where(function ($query) {
                $query->whereNull('reminder_sent_at')
                    ->orWhere('reminder_count', 0);
            })
            ->with(['client', 'plan'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $this->sendReminder($subscription, 1, 3);
        }
    }

    /**
     * Send second reminder (2 days before expiry)
     */
    private function sendSecondReminder(): void
    {
        $subscriptions = Subscription::query()
            ->where('status', true)
            ->whereBetween('expires_at', [
                now()->addDays(2)->startOfDay(),
                now()->addDays(2)->endOfDay()
            ])
            ->where('reminder_count', 1)
            ->with(['client', 'plan'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $this->sendReminder($subscription, 2, 2);
        }
    }

    /**
     * Send final reminder (1 day before expiry)
     */
    private function sendFinalReminder(): void
    {
        $subscriptions = Subscription::query()
            ->where('status', true)
            ->whereBetween('expires_at', [
                now()->addDays(1)->startOfDay(),
                now()->addDays(1)->endOfDay()
            ])
            ->where('reminder_count', 2)
            ->with(['client', 'plan'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $this->sendReminder($subscription, 3, 1);
        }
    }

    /**
     * Send reminder notification to user
     */
    private function sendReminder(Subscription $subscription, int $reminderNumber, int $daysLeft): void
    {
        try {
            $client = $subscription->client;
            app()->setLocale($client->lang ?? 'uz');

            // Check if user has cards for renewal
            $hasCards = $client->cards()
                ->where('verified', true)
                ->exists();

            $message = __('telegram.subscription_expiring_reminder', [
                'days' => $daysLeft,
                'plan' => $subscription->plan->name,
                'expires_at' => $subscription->expires_at->format('d.m.Y H:i')
            ]);

            $keyboard = Keyboard::make();

            if ($hasCards && $subscription->canRenewEarly()) {
                // Add Pay Now button if user has cards and can renew
                $keyboard->buttons([
                    Button::make(__('telegram.pay_now_button'))
                        ->action('renewSubscriptionNow')
                        ->param('subscription_id', $subscription->id)
                ]);
            } else if (!$hasCards) {
                // Add card button if no cards
                $keyboard->buttons([
                    Button::make(__('telegram.add_card_button'))
                        ->action('addCardForRenewal')
                        ->param('subscription_id', $subscription->id)
                ]);
            }

            // Always add view plans button
            $keyboard->row([
                Button::make(__('telegram.view_plans_button'))
                    ->action('showPlans')
            ]);

            Telegraph::chat($client->chat_id)
                ->message($message)
                ->keyboard($keyboard)
                ->send();

            // Update reminder tracking
            $subscription->update([
                'reminder_sent_at' => now(),
                'reminder_count' => $reminderNumber
            ]);

            Log::info('Subscription reminder sent', [
                'subscription_id' => $subscription->id,
                'reminder_number' => $reminderNumber,
                'days_left' => $daysLeft
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send subscription reminder', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}