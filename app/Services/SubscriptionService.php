<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Subscription;
use App\Telegram\Services\HandleChannel;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Create a new subscription for a client
     */
    public function createSubscription(Client $client, Plan $plan, string $receiptId): Subscription
    {
        return DB::transaction(function () use ($client, $plan, $receiptId) {
            // Calculate expiration date based on plan
            $expiresAt = $this->calculateExpirationDate($plan);
            
            // Create subscription
            $subscription = Subscription::create([
                'client_id' => $client->id,
                'plan_id' => $plan->id,
                'receipt_id' => $receiptId,
                'expires_at' => $expiresAt,
                'status' => true,
            ]);
            
            // Add user to channel
            $this->addUserToChannel($client);
            
            // Send confirmation message
            $this->sendSubscriptionConfirmation($client, $subscription);
            
            // Log the new subscription
            Log::info('New subscription created', [
                'subscription_id' => $subscription->id,
                'client_id' => $client->id,
                'plan_id' => $plan->id,
                'expires_at' => $expiresAt,
            ]);
            
            return $subscription;
        });
    }
    
    /**
     * Renew an existing subscription
     */
    public function renewSubscription(Subscription $subscription, string $receiptId): void
    {
        DB::transaction(function () use ($subscription, $receiptId) {
            $plan = $subscription->plan;
            $newExpirationDate = $this->calculateExpirationDate($plan, $subscription->expires_at);
            
            $subscription->update([
                'expires_at' => $newExpirationDate,
                'receipt_id' => $receiptId,
                'status' => true,
                'payment_retry_count' => 0,
                'last_payment_attempt' => now(),
                'last_payment_error' => null,
            ]);
            
            // Ensure user is in channel
            $this->addUserToChannel($subscription->client);
            
            Log::info('Subscription renewed', [
                'subscription_id' => $subscription->id,
                'new_expiration' => $newExpirationDate,
            ]);
        });
    }
    
    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Subscription $subscription, string $reason = null): void
    {
        DB::transaction(function () use ($subscription, $reason) {
            $subscription->update([
                'status' => false,
                'last_payment_error' => $reason,
            ]);
            
            // Remove user from channel
            $this->removeUserFromChannel($subscription->client);
            
            Log::info('Subscription cancelled', [
                'subscription_id' => $subscription->id,
                'reason' => $reason,
            ]);
        });
    }
    
    /**
     * Check if client has an active subscription
     */
    public function hasActiveSubscription(Client $client): bool
    {
        return $client->subscriptions()
            ->where('status', true)
            ->where('expires_at', '>', now())
            ->whereHas('plan', function ($query) {
                $query->where('price', '>', 0);
            })
            ->exists();
    }
    
    /**
     * Get active subscription for client
     */
    public function getActiveSubscription(Client $client): ?Subscription
    {
        return $client->subscriptions()
            ->where('status', true)
            ->where('expires_at', '>', now())
            ->orderBy('expires_at', 'desc')
            ->first();
    }
    
    /**
     * Calculate expiration date based on plan
     */
    private function calculateExpirationDate(Plan $plan, Carbon $startDate = null): Carbon
    {
        $startDate = $startDate ?? now();
        
        // Parse plan name to determine duration
        if (preg_match('/(\d+)-week/', $plan->name, $matches)) {
            return $startDate->copy()->addWeeks((int) $matches[1]);
        } elseif (preg_match('/(\d+)-month/', $plan->name, $matches)) {
            return $startDate->copy()->addMonths((int) $matches[1]);
        } elseif (preg_match('/(\d+)-year/', $plan->name, $matches)) {
            return $startDate->copy()->addYears((int) $matches[1]);
        }
        
        // Default to 1 month if pattern not recognized
        return $startDate->copy()->addMonth();
    }
    
    /**
     * Add user to channel
     */
    private function addUserToChannel(Client $client): void
    {
        try {
            $handleChannel = new HandleChannel($client);
            if ($handleChannel->getChannelUser() === 'unknown') {
                $handleChannel->sendInviteLink();
            }
        } catch (\Exception $e) {
            Log::error('Failed to add user to channel', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Remove user from channel
     */
    private function removeUserFromChannel(Client $client): void
    {
        try {
            $handleChannel = new HandleChannel($client);
            if ($handleChannel->getChannelUser() !== 'unknown') {
                $handleChannel->kickUser();
            }
        } catch (\Exception $e) {
            Log::error('Failed to remove user from channel', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Send subscription confirmation message
     */
    private function sendSubscriptionConfirmation(Client $client, Subscription $subscription): void
    {
        app()->setLocale($client->lang ?? 'uz');
        
        Telegraph::chat($client->chat_id)
            ->message(__('telegram.subscription_success', [
                'date' => $subscription->expires_at->format('Y-m-d'),
            ]))
            ->send();
            
        // Notify admin about new subscription
        if ($adminChatId = config('services.telegram.admin_chat_id')) {
            Telegraph::chat($adminChatId)
                ->message(__('telegram.new_subscription_admin_notification', [
                    'first_name' => $client->first_name,
                    'phone_number' => $client->phone_number,
                    'plan_name' => $subscription->plan->name,
                ]))
                ->send();
        }
    }
}