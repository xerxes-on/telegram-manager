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
    use CanAlterUsers, HasPlans, HandlesButtonActions, CanUsePayme, CanHandleCards;

    public Client $client;


    #[NoReturn] public function start(): void
    {
        Telegraph::chat($this->chat->chat_id)
            ->reactWithEmoji($this->message->id(), '😇')
            ->send();

        $client = $this->getCreateClient();
        Telegraph::chat($this->chat->chat_id)
            ->message(__('telegram.welcome_message'))
            ->send();

        $this->sendClientDetails($client);
        $this->sendPlans();
        die();
    }


    /**
     * Handle incoming chat messages.
     *
     * @throws TelegraphException
     */
    public function handleChatMessage(Stringable $text): void
    {
        $client = $this->getCreateClient();

        match ($text->value()) {
            __('telegram.payment_button') => $this->sendPlans(),
            __('telegram.subscription_status_button') => $this->processSubscriptionStatusButton(),
            __('telegram.help_button') => $this->processSupportButton(),
            __('telegram.change_language_button') => $this->sendLangs(),
            __('telegram.my_card_button') => $this->showMyCards($client)
        };
        match ($client->state) {
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
        if (empty($card = $client->cards()->where('verified', true)->first())) {
            $this->askForCardDetails($client);
        }

        $planModel = Plan::query()->where('name', $plan)->first();

        if ($client->hasUsedFreePlan() && $planModel->name === "one-week-free") {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.free_plan_used'))
                ->send();
            $this->sendPlans();
            return;
        }

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
                        Button::make(__('telegram.change_card_button'))->action('askForCardDetails')->width(0.8),
                        Button::make(__('telegram.back_button'))->action('home')->width(0.2),
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


    protected function handleUnknownCommand(Stringable $text): void
    {
        Telegraph::chat($this->chat->chat_id)
            ->message(__('telegram.command_not_understood'))
            ->replyKeyboard($this->getDefaultKeyboard())
            ->send();
    }
}
