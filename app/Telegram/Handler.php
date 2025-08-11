<?php

namespace App\Telegram;

use App\Enums\ConversationStates;
use App\Models\Client;
use App\Models\Plan;
use App\Telegram\Traits\CanAlterUsers;
use App\Telegram\Traits\CanHandleCards;
use App\Telegram\Traits\CanUsePayme;
use App\Telegram\Traits\HandlesButtonActions;
use App\Telegram\Traits\HasPlans;
use App\Telegram\Traits\TracksMessages;
use App\Telegram\Services\MessageTracker;
use DefStudio\Telegraph\DTO\Contact;
use DefStudio\Telegraph\Exceptions\TelegraphException;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Stringable;
use JetBrains\PhpStorm\NoReturn;

class Handler extends WebhookHandler
{
    use CanAlterUsers, HasPlans, HandlesButtonActions, CanUsePayme, CanHandleCards, TracksMessages;

    public Client $client;

    /**
     * Handle callback queries (inline keyboard button presses)
     */
    protected function handleCallbackQuery(): void
    {
        // Track the original message that had the inline keyboard
        if ($this->callbackQuery && $this->callbackQuery->message()) {
            MessageTracker::trackMessage($this->chat->chat_id, $this->callbackQuery->message()->id());
        }

        parent::handleCallbackQuery();
    }


    public function start(): void
    {
        // Track user's /start message
        if ($this->message && $this->message->id()) {
            MessageTracker::onUserMessage($this->chat->chat_id, $this->message->id());
        }

        if ($this->message) {
            Telegraph::chat($this->chat->chat_id)
                ->reactWithEmoji($this->message->id(), '😇')
                ->send();
        }

        $client = $this->getCreateClient();

        // If client just created, they will be asked for language
        // Otherwise show welcome and plans
        if ($client->state !== ConversationStates::waiting_lang) {
            $this->sendMessage(__('telegram.welcome_message'));

            if ($client->phone_number) {
                $this->sendClientDetails($client);
                $this->sendPlans();
            } else {
                $this->askForPhoneNumber();
            }
        }
        return;
    }


    /**
     * Handle incoming chat messages.
     *
     * @throws TelegraphException
     */
    public function handleChatMessage(Stringable $text): void
    {
        // Track user message
        if ($this->message && $this->message->id()) {
            MessageTracker::onUserMessage($this->chat->chat_id, $this->message->id());
        }

        $client = $this->getCreateClient();

        switch ($text->value()) {
            case __('telegram.payment_button'):
                $this->sendPlans();
                return;
            case __('telegram.subscription_status_button'):
                $this->processSubscriptionStatusButton();
                return;
            case __('telegram.help_button'):
                $this->processSupportButton();
                return;
            case __('telegram.change_language_button'):
                $this->sendLangs();
                return;
            case __('telegram.my_card_button'):
                $this->showMyCards($client);
                return;
            case __('telegram.home_button'):
                $this->goHome($client);
                return;
            default:
                break;
        }

        match ($client->state) {
            ConversationStates::waiting_lang => $this->handleLanguageSelection($client, $text->value()),
            ConversationStates::waiting_phone => $this->processPhoneNumber($client, $this->message->contact()),
            ConversationStates::waiting_card => $this->processCardDetails($client, $text->value()),
            ConversationStates::waiting_card_expire => $this->processCardExpire($client, $text->value()),
            ConversationStates::waiting_card_verify => $this->processVerificationCode($client, $text->value()),
            ConversationStates::chat => Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.misunderstanding'))
                ->replyKeyboard($this->getDefaultKeyboard())
                ->send(),
        };
    }

    private function handleLanguageSelection(Client $client, string $text): void
    {
        // Check if text matches any language option
        $langMap = [
            __('telegram.eng') => 'en',
            __('telegram.ru') => 'ru',
            __('telegram.uz') => 'uz',
            __('telegram.oz') => 'oz',
        ];

        $selectedLang = null;
        foreach ($langMap as $label => $code) {
            if ($text === $label || strpos($text, $code) !== false) {
                $selectedLang = $code;
                break;
            }
        }

        if ($selectedLang) {
            $client->update(['lang' => $selectedLang]);
            $this->setState($client, ConversationStates::waiting_phone);
            app()->setLocale($selectedLang);

            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.welcome_message'))
                ->send();

            $this->askForPhoneNumber();
        } else {
            $this->sendLangs();
        }
    }

    private function processPhoneNumber(Client $client, Contact $contact): void
    {
        $client->update(['phone_number' => $contact->phoneNumber()]);
        $this->setLanguage($client);

        $this->setState($client, ConversationStates::chat);

        $this->reply(__('telegram.phone_saved'), true);
//            $this->showLanguageSelection();
        Telegraph::chat($this->chat->chat_id)
            ->message(__('telegram.thank_you_data_saved'))
            ->replyKeyboard($this->getDefaultKeyboard())
            ->send();

        $this->sendPlans();
    }

    /**
     * Handles a valid phone number by setting the state and prompting for the user's name.
     */
    public function savePlan(string $plan): void
    {
        Telegraph::chat($this->chat->chat_id)->deleteMessage($this->messageId)->send();
        $client = $this->getCreateClient();
        $planModel = Plan::query()->where('name', $plan)->first();

        if (!$planModel) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.plan_not_found'))
                ->send();
            return;
        }

        // Check if user has already used free plan
        if ($client->hasUsedFreePlan() && $planModel->name === "one-week-free") {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.free_plan_used'))
                ->send();
            $this->sendPlans();
            return;
        }

        // Store selected plan in cache for later use
        cache()->put("selected_plan_{$client->id}", $planModel->id, now()->addMinutes(30));

        // Check if user has a verified main card
        $card = $client->cards()->where(['verified' => true, 'is_main' => true])->first();

        if (!$card) {
            if ($planModel->price == 0) {
                Telegraph::chat($this->chat->chat_id)
                    ->message(__('telegram.free_plan_card_explanation'))->send();
            }
            $this->askForCardDetails($client);
            return; // Exit here, will continue after card is added
        }

        // Show confirmation
        Telegraph::chat($this->chat->chat_id)
            ->html(__('telegram.subscription_confirmation', [
                'plan_name' => $planModel->name,
                'plan_price' => $planModel->price / 100,
                'card_number' => $card->masked_number,
            ]))
            ->keyboard(
                Keyboard::make()
                    ->buttons([
                        Button::make(__('telegram.confirm_button'))->action('payPayme')->param('plan_id', $planModel->id)->width(1),
                        Button::make(__('telegram.change_card_button'))->action('showMyCardsAction')->width(0.8),
                        Button::make(__('telegram.back_button'))->action('goHomeAction')->width(0.2),
                    ]))
            ->send();
    }

    public function payPayme(string $plan_id): void
    {
        Telegraph::chat($this->chat->chat_id)->deleteMessage($this->messageId)->send();

        $plan = Plan::query()->find($plan_id);
        $client = $this->getCreateClient();
        if ($client->hasActiveSubscription()) {
            Telegraph::chat($client->chat_id)
                ->message(__('telegram.subscription_already_active'))
                ->send();
            return;
        }
        if ($plan->price === 0) {
            $this->createFreePlan($client, $plan);
        } else {
            $this->callRecurrentPay($client, $plan);
        }
    }

    public function pay_now(): void
    {
        Telegraph::chat($this->chat->chat_id)->deleteMessage($this->messageId)->send();

        $client = $this->getCreateClient();

        // Check if client has an active subscription to renew
        $subscription = $client->subscriptions()->where('status', true)->latest()->first();

        if (!$subscription) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.no_active_subscription'))
                ->send();
            $this->sendPlans();
            return;
        }

        // Get the plan from the subscription
        $plan = $subscription->plan;

        // Check if the current plan is a free plan
        if ($plan->price === 0) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.free_plan_upgrade_prompt'))
                ->send();
            $this->sendPlans();
            return;
        }

        // Check if user has a verified main card
        $card = $client->cards()->where(['verified' => true, 'is_main' => true])->first();

        if (!$card) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.no_card_for_payment'))
                ->send();
            $this->askForCardDetails($client);
        }

        // Show confirmation
        Telegraph::chat($this->chat->chat_id)
            ->html(__('telegram.subscription_renewal_confirmation', [
                'plan_name' => $plan->name,
                'plan_price' => $plan->price / 100,
                'card_number' => $card->masked_number,
            ]))
            ->keyboard(
                Keyboard::make()
                    ->buttons([
                        Button::make(__('telegram.confirm_button'))->action('payPayme')->param('plan_id', $plan->id)->width(1),
                        Button::make(__('telegram.change_card_button'))->action('showMyCardsAction')->width(0.8),
                        Button::make(__('telegram.back_button'))->action('goHomeAction')->width(0.2),
                    ]))
            ->send();
    }


    protected function handleUnknownCommand(Stringable $text): void
    {
        Telegraph::chat($this->chat->chat_id)
            ->message(__('telegram.command_not_understood'))
            ->replyKeyboard($this->getDefaultKeyboard())
            ->send();
    }
}
