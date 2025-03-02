<?php

namespace App\Telegram\Traits;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Telegram\Services\HandleChannel;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;

trait CanCreateSubscription
{
    private function createSubscription(Plan $plan, string $receiptId): void
    {
        $user = User::where('chat_id', $this->chat_id)->first();
        $planTitle = $plan->name;
        $expires = match (true) {
            str_contains($planTitle, 'one-month') => Carbon::now()->addMonth(),
            str_contains($planTitle, 'two-months') => Carbon::now()->addMonths(2),
            str_contains($planTitle, 'six-months') => Carbon::now()->addMonths(6),
            str_contains($planTitle, 'one-year') => Carbon::now()->addYear(),
            default => Carbon::now()->addWeek()
        };
        $user->subscriptions()->where('status', true)->latest()?->first()?->deactivate();
        Subscription::create([
            'user_id' => $user->id,
            'receipt_id' => $receiptId,
            'amount' => $plan->price,
            'expires_at' => $expires,
            'status' => 1,
            'plan_id' => $plan->id
        ]);

        $this->chat_id = $user->chat_id;
        $this->admin_chat_id = intval(env("ADMIN_CHAT_ID"));
        Telegraph::chat($this->chat_id)
            ->sticker("CAACAgIAAxkBAAExKjRnl0Nr7-7-U-Ita4YDc764z65TRwACiQADFkJrCkbL2losgrCONgQ")
            ->send();

        Telegraph::chat($this->chat_id)
            ->message("To'g'ri tanlov! \nObuna: ".$expires->format('Y-m-d')." gacha 😇")
            ->send();

        $handler = new HandleChannel($this->getUser($this->chat_id));
        $handler->generateInviteLink();

        Telegraph::chat($this->admin_chat_id)
            ->message("Yangi obuna yaratildi 🎉.\nIsm: ".$user->name." \nTel raqam: ".$user->phone_number."\nObuna: ".$plan->name)
            ->send();
    }

}
