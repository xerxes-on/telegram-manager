<?php

namespace App\Jobs\services;

use App\Jobs\Interfaces\SubscriptionNotifier;
use App\Models\Client;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

class TelegramSubscriptionNotifier implements SubscriptionNotifier
{
    public function notify(Client $client, int $daysLeft): void
    {
        $message = __('telegram.subscription_reminder_greeting', ['first_name' => $client->first_name]) . "\n\n" .
            __('telegram.subscription_reminder_message', ['days_left' => $daysLeft]) . "\n" .
            __('telegram.subscription_reminder_footer');

        Telegraph::chat($client->chat_id)
            ->html($message)
            ->keyboard(  Keyboard::make()->buttons([Button::make(__('telegram.pay_now_button'))->action('pay_now')]))
            ->send();
    }
}
