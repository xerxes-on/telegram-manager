<?php

namespace App\Telegram\Traits;

use App\Models\Card;
use App\Models\Plan;
use App\Models\User;
use App\Telegram\Services\PaycomSubscriptionService;

trait CanUsePayme
{
    public function callCreateCard(string $card, string $expire, User $user): bool
    {
        $service = new PaycomSubscriptionService($this->chat_id());
        return $service->cardsCreate($card, $expire, $user);
    }

    public function callVerifyCard(User $user, string $code): bool
    {
        $card = Card::where('user_id', $user->id)->where('verified', false)->latest()->first();
        if (!$card) {
            return false;
        }
        $service = new PaycomSubscriptionService($this->chat_id());
        return $service->verifyCard($card->token, $code);
    }

    public function callRecurrentPay(Plan $plan, User $user): void
    {

        $service = new PaycomSubscriptionService($this->chat_id());
        $service->receiptsCreate($plan, $user);
    }
}
