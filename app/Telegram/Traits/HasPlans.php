<?php

namespace App\Telegram\Traits;

use App\Enums\ConversationStates;
use App\Models\Client;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use Illuminate\Support\Facades\Cache;
use JetBrains\PhpStorm\NoReturn;

/**
 * Trait for handling subscription plans and card input flow.
 */
trait HasPlans
{
    #[NoReturn] public function processCardDetails(Client $client, string $card): void
    {
        $card = str_replace(' ', '', $card);
        $rules = '/^\d{16}$/';
        if (empty($card) || !preg_match($rules, $card)) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.invalid_card_number'))
                ->send();
        } else {
            if (!Cache::has($this->chat->chat_id . "card")) {
                Cache::put($this->chat->chat_id . "card", $card, now()->addMinutes(10));
            }
            $this->setState($client, ConversationStates::waiting_card_expire);
            Telegraph::chat($client->chat_id)->message(__('telegram.ask_for_card_expiry'))
                ->replyKeyboard(ReplyKeyboard::make()
                    ->row([
                        ReplyButton::make(__('telegram.help_button')),
                        ReplyButton::make(__('telegram.home_button')),
                    ])->chunk(2)
                    ->row([
                        ReplyButton::make(__('telegram.change_language_button')),
                    ])->chunk(1)
                    ->resize()
                )->send();
        }
        return;
    }

    #[NoReturn] public function processCardExpire(Client $client, string $expire): void
    {
        $card = Cache::get($this->chat->chat_id . "card");
        if (empty($card)) {
            $this->askForCardDetails($client);
        }
        $expire = trim($expire);
        $rules = '/^(0[1-9]|1[0-2])\/\d{2}$/';
        if (empty($expire) || !preg_match($rules, $expire)) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.invalid_expiry_date'))
                ->send();
            return;
        }
        list($month, $year) = explode('/', $expire);
        $month = (int)$month;
        $year = (int)('20' . $year);
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('m');
        if ($year < $currentYear || ($year == $currentYear && $month < $currentMonth)) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.card_expired'))
                ->send();
            return;
        }

        list($month, $year) = explode('/', $expire);
        $expire = $month . $year;
        $this->callCreateCard($card, $expire, $client);
        return;
    }

    #[NoReturn] public function askForCardDetails(Client $client): void
    {
        Telegraph::chat($this->chat->chat_id)->deleteMessage($this->messageId)->send();
        $this->setState($client, ConversationStates::waiting_card);
        Telegraph::chat($this->chat->chat_id)->message(__('telegram.ask_for_card_number'))
            ->replyKeyboard(ReplyKeyboard::make()
                ->row([
                    ReplyButton::make(__('telegram.help_button')),
                    ReplyButton::make(__('telegram.home_button')),
                ])->chunk(2)
                ->row([
                    ReplyButton::make(__('telegram.change_language_button')),
                ])->chunk(1)
                ->resize()
            )->send();
        return;
    }

    #[NoReturn] public function processVerificationCode(Client $client, string $code): void
    {
        $card = $client->cards()->latest()->first();
        if (empty($card)) {
            $this->askForCardDetails($client);
        }
        $verified = $this->callVerifyCard($client, $code, $card);
        Cache::forget($this->chat->chat_id . "card");
        if ($verified) {
            $this->sendPlans();
        } else {
            $this->askForCardDetails($client);
        }
        return;
    }

    private function sendPlans(): void
    {
        Telegraph::chat($this->chat->chat_id)
            ->message(__('telegram.select_plan_duration'))
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make(__('telegram.one_week_free'))->action('savePlan')->param('plan', 'one-week-free')->width(0.3),
                    Button::make(__('telegram.one_month'))->action('savePlan')->param('plan', 'one-month')->width(0.3),
                    Button::make(__('telegram.two_months'))->action('savePlan')->param('plan', 'two-months')->width(0.3),
                    Button::make(__('telegram.six_months'))->action('savePlan')->param('plan', 'six-months')->width(0.5),
                    Button::make(__('telegram.one_year'))->action('savePlan')->param('plan', 'one-year')->width(0.5),
                ])
            )
            ->send();
    }
}
