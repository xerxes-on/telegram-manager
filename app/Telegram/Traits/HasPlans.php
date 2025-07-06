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
                ->message("Kiritilgan karta raqami noto'g'ri. Iltimos, 16 xonali karta raqamini qayta kiriting:")
                ->send();
        } else {
            if (!Cache::has($this->chat->chat_id . "card")) {
                Cache::put($this->chat->chat_id . "card", $card, now()->addMinutes(10));
            }
            $this->setState($client, ConversationStates::waiting_card_expire);
            Telegraph::chat($client->chat_id)->message("💳Amal qilish muddatini yuboring (10/29): ")
                ->replyKeyboard(ReplyKeyboard::make()
                    ->row([
                        ReplyButton::make(__('telegram.help_button')),
                        ReplyButton::make(__('telegram.home_button')),
                    ])->chunk(2)
                    ->resize()
                )->send();
        }
        die();
    }

    #[NoReturn] public function processCardExpire(Client $client, string $expire): void
    {
        $card = Cache::get($this->chat->chat_id . "card");
        if (empty($card)) {
            $this->askForCardDetails($client);
            die();
        }
        $expire = trim($expire);
        $rules = '/^(0[1-9]|1[0-2])\/\d{2}$/';
        if (empty($expire) || !preg_match($rules, $expire)) {
            Telegraph::chat($this->chat->chat_id)
                ->message("Kiritilgan sana noto'g'ri. Masalan: 10/30 yoki 02/28")
                ->send();
            die();
        }
        list($month, $year) = explode('/', $expire);
        $month = (int)$month;
        $year = (int)('20' . $year);
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('m');
        if ($year < $currentYear || ($year == $currentYear && $month < $currentMonth)) {
            Telegraph::chat($this->chat->chat_id)
                ->message("Karta amal qilish muddati tugagan. Iltimos, to'g'ri amal qilish muddatini kiriting.")
                ->send();
            die();
        }

        list($month, $year) = explode('/', $expire);
        $expire = $month . $year;
        $this->callCreateCard($card, $expire, $client);
        die();
    }

    #[NoReturn] public function askForCardDetails(Client $client): void
    {
        Telegraph::chat($this->chat->chat_id)->deleteMessage($this->messageId)->send();
        $this->setState($client, ConversationStates::waiting_card);
        Telegraph::chat($this->chat->chat_id)->message("💳 Karta raqamini yuboring:")->send();
        die();
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
        }
        die();
    }

    private function sendPlans(): void
    {
        Telegraph::chat($this->chat->chat_id)
            ->message('Obuna muddatini tanlang 👇')
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make('1-hafta bepul')->action('savePlan')->param('plan', 'one-week-free')->width(0.3),
                    Button::make('1 oy')->action('savePlan')->param('plan', 'one-month')->width(0.3),
                    Button::make('2 oy')->action('savePlan')->param('plan', 'two-months')->width(0.3),
                    Button::make('6 oy')->action('savePlan')->param('plan', 'six-months')->width(0.5),
                    Button::make('1 yil')->action('savePlan')->param('plan', 'one-year')->width(0.5),
                ])
            )
            ->send();
    }
}
