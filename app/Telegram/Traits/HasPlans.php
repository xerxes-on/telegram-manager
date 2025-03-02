<?php

namespace App\Telegram\Traits;

use App\Models\Card;
use App\Models\Plan;
use App\Models\User;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Facades\Cache;

/**
 * Trait for handling subscription plans and card input flow.
 */
trait HasPlans
{
    public function processCardDetails($card): void
    {
        $card = str_replace(' ', '', $card);
        $rules = '/^\d{16}$/';
        if (empty($card) || !preg_match($rules, $card)) {
            Telegraph::chat($this->chat_id())
                ->message("Kiritilgan karta raqami noto'g'ri. Iltimos, 16 xonali karta raqamini qayta kiriting:")
                ->send();
        } else {
            $this->clearState($this->chat_id());
            if (!Cache::has($this->chat_id()."card")) {
                Cache::put($this->chat_id()."card", $card, now()->addMinutes(10));
            }
            $this->askForExpireDate();
        }
    }

    public function askForExpireDate(): void
    {
        $this->setState($this->chat_id(), 'waiting_for_card_expire');
        Telegraph::chat($this->chat_id())->message("💳Amal qilish muddatini yuboring (10/29): ")->send();
    }

    public function processCardExpire(string $expire): void
    {
        if (empty(Cache::get($this->chat_id()."card"))) {
            $this->askForCardDetails();
            return;
        }
        $expire = trim($expire);
        $rules = '/^(0[1-9]|1[0-2])\/\d{2}$/';
        if (empty($expire) || !preg_match($rules, $expire)) {
            Telegraph::chat($this->chat_id())
                ->message("Kiritilgan sana noto'g'ri. Masalan: 10/30 yoki 02/28")
                ->send();
            return;
        }
        list($month, $year) = explode('/', $expire);
        $month = (int) $month;
        $year = (int) ('20'.$year);
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');
        if ($year < $currentYear || ($year == $currentYear && $month < $currentMonth)) {
            Telegraph::chat($this->chat_id())
                ->message("Karta amal qilish muddati tugagan. Iltimos, to'g'ri amal qilish muddatini kiriting.")
                ->send();
            return;
        }
        $this->clearState($this->chat_id());
        list($month, $year) = explode('/', $expire);
        $state = $this->callCreateCard(Cache::get($this->chat_id()."card"), $month.$year,
            User::where('chat_id', $this->chat_id())->first());
        if (!$state) {
            $this->askForCardDetails();
        }
    }

    public function askForCardDetails(): void
    {
        $card = Card::where('user_id', User::where('chat_id', $this->chat_id())->first()->id)->first();
        if (!empty($card)) {
            $card->delete();
        }
        $this->setState($this->chat_id(), 'waiting_for_card');
        Telegraph::chat($this->chat_id())->message("💳Karta raqamini yuboring:")->send();
    }

    public function processVerificationCode(string $code): void
    {
        $user = User::where('chat_id', $this->chat_id())->first();
        $state = $this->callVerifyCard($user, $code);
        Cache::forget($this->chat_id()."card");
        if ($state) {
            $this->sendPlans();
        }
    }

    /**
     * Show the available subscription plans.
     *
     * @return void
     */
    private function sendPlans(): void
    {
        Telegraph::chat($this->chat_id())
            ->message('Obuna muddatini tanlang 👇')
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make('1-hafta bepul')->action('savePlan')->param('plan', 'one-week-free')->width(0.3),
                    Button::make('1 oy')->action('savePlan')->param('plan', 'one-month')->width(0.3),
                    Button::make('2 oy')->action('savePlan')->param('plan', 'two-months')->width(0.3),
                    Button::make('6 oy')->action('savePlan')->param('plan', 'six-months')->width(0.5),
                    Button::make('1 yil')->action('savePlan')->param('plan', 'one-year')->width(0.5),
                ])
            )->send();
    }

    public function hasVerifiedCard(): bool
    {
        $user = User::where('chat_id', $this->chat_id())->first();
        $card = Card::where('user_id', $user->id)->where('verified', true)->first();
        return !empty($card);
    }
}
