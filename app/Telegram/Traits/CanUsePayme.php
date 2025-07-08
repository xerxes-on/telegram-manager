<?php

namespace App\Telegram\Traits;

use App\Models\Card;
use App\Models\Client;
use App\Models\Order;
use App\Models\Plan;
use App\Telegram\Services\PaycomSubscriptionService;

trait CanUsePayme
{
    public function callCreateCard(string $card, string $expire, Client $client): bool
    {
        $service = new PaycomSubscriptionService($this->chat->chat_id);
        return $service->cardsCreate($card, $expire, $client);
    }
    public function callVerifyToken(Client $client, Card $card): bool
    {
        $service = new PaycomSubscriptionService($this->chat->chat_id);
        return $service->cardsSendVerifyCode($client, $card->token);
    }

    public function callVerifyCard(Client $client, string $code, Card $card): bool
    {
        $service = new PaycomSubscriptionService($this->chat->chat_id);
        return $service->verifyCard($card, $code);
    }

    public function callRecurrentPay(Client $client, Plan $plan): void
    {
        $service = new PaycomSubscriptionService($this->chat->chat_id);
        $order = Order::query()->where('client_id', $client->id)
            ->where('plan_id', $plan->id)
            ->where('price', $plan->price)
            ->where('status', 'created')
            ->first();

        if (!$order) {
            $order = Order::query()->create([
                'client_id' => $client->id,
                'plan_id' => $plan->id,
                'price' => $plan->price,
                'status' => 'created',
            ]);
        }
        $service->receiptsCreate($plan, $client, $order);
    }

    public function createFreePlan(Client $client, Plan $plan): void
    {
        $service = new PaycomSubscriptionService($this->chat->chat_id);
        $service->createFreePlan($client, $plan);
    }
}
