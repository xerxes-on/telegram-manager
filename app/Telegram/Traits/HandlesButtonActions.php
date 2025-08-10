<?php

namespace App\Telegram\Traits;

use App\Enums\ConversationStates;
use App\Models\Client;
use App\Models\Subscription;
use App\Telegram\Services\HandleChannel;
use App\Telegram\Services\MessageTracker;
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
        $supportPhone = config('services.support.phone_number', '+998901234567');
        $supportTelegram = config('services.support.telegram_username', '@xerxeson');
        
        Telegraph::chat($this->chat->chat_id)
            ->message(__('telegram.support_text_detailed', [
                'phone' => $supportPhone,
                'telegram' => $supportTelegram
            ]))
            ->send();
    }

    /**
     * @throws TelegraphException
     */
    public function processSubscriptionStatusButton(): void
    {
        Telegraph::chat($this->chat->chat_id)
            ->chatAction(ChatActions::TYPING)
            ->send();

        $client = Client::query()->where('chat_id', $this->chat->chat_id)->first();
        if (!$client) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.no_active_subscription'))
                ->send();
            return;
        }
        $sub = Subscription::query()
            ->where('status', 1)
            ->where('client_id', $client->id)
            ->orderByDesc('expires_at')
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

    public function confirmDeletion(): void
    {
        Telegraph::chat($this->chat->chat_id)
            ->message(__('telegram.confirm_delete'))
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make(__('telegram.no_button'))->action('goHomeAction')->width(0.2),
                    Button::make(__('telegram.yes_button'))->action('cancelPlan')->width(0.8)
                ]))
            ->send();
    }

    public function cancelPlan(): void
    {
        $user = $this->getCreateClient();
        $service = new HandleChannel($user);
        $service->kickUser();
        $user->subscriptions()->delete();
    }

    public function goHome(Client $client): void
    {
        $this->setState($client, ConversationStates::chat);
        
        // Send welcome back message with default keyboard
        $this->telegraph()
            ->message(__('telegram.welcome_back'))
            ->replyKeyboard($this->getDefaultKeyboard())
            ->send();
            
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
                ReplyButton::make(__('telegram.change_language_button')),
            ])->chunk(2)
            ->row([
                ReplyButton::make(__('telegram.my_card_button')),
            ])->chunk(1)
            ->resize();
    }

    public function setLanguage(Client $client = null): void
    {
        $lang = $client?->lang ?: config('app.locale', 'uz');
        if (!in_array($lang, ['uz', 'ru', 'oz'])) {
            $lang = 'uz';
        }
        app()->setLocale($lang);
    }

    public function sendLangs(): void
    {
        $response = Telegraph::chat($this->chat->chat_id)
            ->message(__('telegram.choose_lang'))
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make(__('telegram.ru'))->action('changeLanguage')->param('code', 'ru')->width(0.33),
                    Button::make(__('telegram.uz'))->action('changeLanguage')->param('code', 'uz')->width(0.33),
                    Button::make(__('telegram.oz'))->action('changeLanguage')->param('code', 'oz')->width(0.33)
                ]))
            ->send();
            
        if ($response->successful() && isset($response->json()['result']['message_id'])) {
            MessageTracker::trackMessage($this->chat->chat_id, $response->json()['result']['message_id']);
        }
    }

    public function changeLanguage(string $code): void
    {
        $client = $this->getCreateClient();
        $client->update(['lang' => $code]);
        app()->setLocale($code);
        Telegraph::chat($this->chat->chat_id)->deleteMessage($this->messageId)->send();
        
        // If this is initial language selection, move to phone number
        if ($client->state === ConversationStates::waiting_lang) {
            $this->setState($client, ConversationStates::waiting_phone);
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.welcome_message'))
                ->send();
            $this->askForPhoneNumber();
        } else {
            Telegraph::chat($this->chat->chat_id)->message('✅')->replyKeyboard($this->getDefaultKeyboard())->send();
        }
    }
    public function showMyCardsAction(): void
    {
        $client = $this->getCreateClient();
        Telegraph::chat($this->chat->chat_id)->deleteMessage($this->messageId)->send();
        $this->showMyCards($client);
    }
    public function goHomeAction(): void
    {
        $client = $this->getCreateClient();
        Telegraph::chat($this->chat->chat_id)->deleteMessage($this->messageId)->send();
        $this->goHome($client);
    }
}
