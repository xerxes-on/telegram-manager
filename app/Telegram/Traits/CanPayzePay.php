<?php

namespace App\Telegram\Traits;

use App\Models\Plan;
use App\Models\User;
use App\Telegram\Services\PayzePaymentService;
use DefStudio\Telegraph\Facades\Telegraph;

trait CanPayzePay
{
    public function processPaymentOneTime(Plan $plan, User $user): void
    {
        if (!$user) {
            Telegraph::chat($chatId)->message("User not found!")->send();
            return;
        }

        $payzeService = app(PayzePaymentService::class);
        $paymentUrl = $payzeService->createOneTimePayment($user, $amount, 'UZS');

        Telegraph::chat($chatId)
            ->message("😇 To'lovni amalga oshirishingiz mumkin: $paymentUrl")
            ->send();
    }

    public function processSubscriptionStatusButton(): void
    {
        $chatId = $this->chat_id();
        $user = User::where('chat_id', $chatId)->first();
        if (!$user) {
            Telegraph::chat($chatId)->message("User not found!")->send();
            return;
        }
        if ($user->hasActiveSubscription()) {
            $expires = $user->subscription_expires_at;
            Telegraph::chat($chatId)
                ->message("Your subscription is active until: $expires")
                ->send();
        } else {
            Telegraph::chat($chatId)
                ->message("You don't have an active subscription.")
                ->send();
        }
    }
}
