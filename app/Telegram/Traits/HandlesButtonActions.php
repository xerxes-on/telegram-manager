<?php

namespace App\Telegram\Traits;

use App\Enums\ConversationStates;
use App\Models\Client;
use App\Models\Subscription;
use App\Telegram\Services\HandleChannel;
use DefStudio\Telegraph\Enums\ChatActions;
use DefStudio\Telegraph\Exceptions\TelegraphException;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;

trait HandlesButtonActions
{
    public function processSupportButton(): void
    {
        Telegraph::chat($this->chat->chat_id)
            ->message(__('telegram.support_text'))
            ->send();
    }

    /**
     * @throws TelegraphException
     */
    public function processSubscriptionStatusButton(): void
    {
        Telegraph::chat($this->chat->chat_id)
            ->chatAction(ChatActions::CHOOSE_STICKER)
            ->send();

        $sub = Subscription::query()
            ->where('status', 1)
            ->where('client_id',
                Client::query()
                    ->where('chat_id', $this->chat->chat_id)
                    ->first()->id)
            ->first();
        if (empty($sub)) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.no_active_subscription'))
                ->send();
            return;
        }
        Telegraph::chat($this->chat->chat_id)
            ->message(__('telegram.subscription_expires_at', ['date' => $sub->expires_at]))
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make(__('telegram.cancel_button'))->action('confirmDeletion')
                ]))
            ->send();
    }

    public function home(): void
    {
//        $id = $this->request['message']['id'] - 1;
//        if (!is_null($id)) {
//            Telegraph::chat($this->chat->id)
//                ->deleteMessage($id)
//                ->send();
//        }
        $this->sendPlans();
    }

    public function confirmDeletion(): void
    {
        Telegraph::chat($this->chat->chat_id)
            ->message(__('telegram.confirm_delete'))
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make(__('telegram.no_button'))->action('getDefaultKeyboard')->width(0.2),
                    Button::make(__('telegram.yes_button'))->action('cancelPlan')->width(0.8)
                ]))
            ->send();
    }

    public function cancelPlan(): void
    {
        $user = Client::query()->where('chat_id', $this->chat->chat_id)->first();
        $service = new HandleChannel($user);
        $service->kickUser();
        $user->subscriptions()->latest()->delete();
    }
    public function goHome(Client $client): void
    {
        $this->setState($client, ConversationStates::chat);
        $this->sendPlans();
    }

    public function getDefaultKeyboard(): ReplyKeyboard
    {
        return ReplyKeyboard::make()
            ->row([
                ReplyButton::make(__('telegram.payment_button')),
                ReplyButton::make(__('telegram.subscription_status_button')),
            ])->chunk(2)
            ->row([
                ReplyButton::make(__('telegram.help_button')),
            ])->chunk(1)
            ->resize();
    }
}
