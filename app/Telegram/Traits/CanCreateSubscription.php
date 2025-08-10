<?php

namespace App\Telegram\Traits;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Subscription;
use App\Telegram\Services\HandleChannel;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;

trait CanCreateSubscription
{
    private function createSubscription(Client $client, Plan $plan, string $receiptId): void
    {
        $planTitle = $plan->name;
        $expires = match (true) {
            str_contains($planTitle, 'one-month') => Carbon::now()->addMonth(),
            str_contains($planTitle, 'two-months') => Carbon::now()->addMonths(2),
            str_contains($planTitle, 'six-months') => Carbon::now()->addMonths(6),
            str_contains($planTitle, 'one-year') => Carbon::now()->addYear(),
            default => Carbon::now()->addWeek()
        };
        $client->subscriptions()->where('status', true)->latest()?->first()?->deactivate();
        Subscription::query()->create([
            'client_id' => $client->id,
            'receipt_id' => $receiptId,
            'expires_at' => $expires,
            'status' => 1,
            'plan_id' => $plan->id
        ]);

        Telegraph::chat($client->chat_id)
            ->sticker("CAACAgIAAxkBAAExKjRnl0Nr7-7-U-Ita4YDc764z65TRwACiQADFkJrCkbL2losgrCONgQ")
            ->send();

        Telegraph::chat($client->chat_id)
            ->message(__('telegram.subscription_success', ['date' => $expires->format('Y-m-d')]))
            ->send();

        $handler = new HandleChannel($client);
        $handler->generateInviteLink();

        Telegraph::chat(intval(env("ADMIN_CHAT_ID")))
            ->message(__('telegram.new_subscription_admin_notification', [
                'first_name' => $client->first_name,
                'phone_number' => $client->phone_number,
                'plan_name' => $plan->name,
            ]))
            ->send();
    }

}
