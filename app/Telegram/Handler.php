<?php

namespace App\Telegram;

use App\Models\Card;
use App\Models\Plan;
use App\Models\User;
use App\Telegram\Services\HandleChannel;
use App\Telegram\Traits\CanAlterUsers;
use App\Telegram\Traits\CanUsePayme;
use App\Telegram\Traits\HandlesButtonActions;
use App\Telegram\Traits\HasPlans;
use DefStudio\Telegraph\Enums\ChatActions;
use DefStudio\Telegraph\Exceptions\TelegraphException;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use Illuminate\Support\Stringable;

class Handler extends WebhookHandler
{
    use CanAlterUsers, HasPlans, HandlesButtonActions, CanUsePayme;

    public function start(): void
    {
        Telegraph::chat($this->chat_id())
            ->reactWithEmoji($this->request['message']['message_id'], '😇')->send();

        Telegraph::chat($this->chat_id())
            ->message(
                "Assalomu aleykum! Yangi \"Anvar Abduqayum Full Contact\" loyihamizga xush kelibsiz!\n\n".
                "Ushbu kanal uchun alohida vaqt ajratib zavq bilan ".
                "yondashishga harakat qilaman!\n\n".
                "Bu kanalda:\n".
                "1. Faqat foydali content.\n".
                "Oylik obuna 500 000 so'm."
            )
            ->send();

        // Check if user is already registered
        if ($this->isRegistered($this->chat_id())) {
            $this->sendUserData($this->chat_id());
            $this->sendPlans();
        } else {
            // If not registered, set state to request phone number
            $this->askForPhoneNumber();
        }
    }

    public function chat_id(): int
    {
        return $this->chat->chat_id;
    }

    /**
     * @throws TelegraphException
     */
    public function handleChatMessage(Stringable $text): void
    {
        $chatId = $this->chat_id();
        $stateArr = $this->getState($chatId);
        $state = $stateArr['state'] ?? null;
        $data = $stateArr['data'] ?? null;

        $message = trim($text);
        Telegraph::chat($chatId)->chatAction(ChatActions::TYPING)->send();

        switch ($message) {
            case "💳 To'lov":
                $this->hasVerifiedCard() ? $this->sendPlans() : $this->askForCardDetails();
                return;

            case '📋 Obuna holati':
                $this->processSubscriptionStatusButton();
                return;

            case "🆘 Yordam":
                $this->processSupportButton();
                return;
        }

        switch ($state) {
            case 'waiting_for_phone':
                $this->processPhoneNumber($chatId, $message);
                return;

            case 'waiting_for_name':
                $this->processUserName($chatId, $message, $data);
                return;
            case 'waiting_for_card':
                $this->processCardDetails($message);
                return;
            case 'waiting_for_card_expire':
                $this->processCardExpire($message);
                return;
            case 'waiting_for_verify':
                $this->processVerificationCode($message);
                return;

        }
        $keyboard = ReplyKeyboard::make()
            ->row([
                ReplyButton::make("💳 To'lov"),
                ReplyButton::make("📋 Obuna holati"),
            ])->chunk(2)
            ->row([
                ReplyButton::make('🆘 Yordam'),
            ])->chunk(1)
            ->resize();
        Telegraph::chat($chatId)
            ->message("🤷‍ Kechirasiz, bu buyruqni tushunmadim.")
            ->replyKeyboard($keyboard)
            ->send();
    }

    private function processPhoneNumber(int $chatId, string $message): void
    {
        $contact = $this->request['message']['contact'] ?? null;
        $name = $this->request['message']['from']['first_name'] ?? "Anonymous";
        if ($contact) {
            $phoneNumber = $contact['phone_number'] ?? null;
            if ($phoneNumber && preg_match('/^\+?[0-9]{10,15}$/', $phoneNumber)) {
                $this->setState($chatId, 'waiting_for_name', $phoneNumber);
                Telegraph::chat($chatId)
                    ->reactWithEmoji($this->request['message']['message_id'], '❤️')
                    ->send();
                Telegraph::chat($chatId)
                    ->message("Telefon raqamingiz qabul qilindi! Endi ismingizni yuboring:")
                    ->replyKeyboard(ReplyKeyboard::make()
                        ->button($name)->resize())
                    ->send();
                return;
            }
        }

        if (preg_match('/^\+?[0-9]{10,13}$/', $message)) {

            $this->setState($chatId, 'waiting_for_name', $message);
            Telegraph::chat($chatId)
                ->reactWithEmoji($this->request['message']['message_id'], '❤️')
                ->send();
            Telegraph::chat($chatId)
                ->message("Telefon raqamingiz qabul qilindi! Endi ismingizni yuboring:")
                ->replyKeyboard(ReplyKeyboard::make()
                    ->button($name)->resize())
                ->send();
        } else {
            Telegraph::chat($chatId)
                ->message("Noto'g'ri telefon raqam. Iltimos, qayta urinib ko'ring yoki raqamни контакт орқали юборинг.")
                ->send();
        }
    }

    private function processUserName(int $chatId, string $message, string $phoneNumber): void
    {
        if (empty($message)) {
            Telegraph::chat($chatId)
                ->message("Ismingiz bo'sh bo'lishi mumkin emas. Qayta kiritib ko'ring:")
                ->send();
            return;
        }
        $userId = $this->request['message']['from']['id'];
        User::create([
            'user_id' => $userId,
            'chat_id' => $chatId,
            'phone_number' => $phoneNumber,
            'name' => $message,
        ]);
        Telegraph::chat($chatId)
            ->reactWithEmoji($this->request['message']['message_id'], '❤️')
            ->send();
        $this->clearState($chatId);

        $keyboard = ReplyKeyboard::make()
            ->row([
                ReplyButton::make("💳 To'lov"),
                ReplyButton::make("📋 Obuna holati"),
            ])->chunk(2)
            ->row([
                ReplyButton::make('🆘 Yordam'),
            ])->chunk(1)
            ->resize();
        Telegraph::chat($chatId)
            ->message("Rahmat, $message! Ma'lumotlaringiz saqlandi. 🎉")
            ->replyKeyboard($keyboard)
            ->send();

        $this->sendPlans();
    }

    public function savePlan(string $plan): void
    {
        if (!$this->hasVerifiedCard()) {
            $this->askForCardDetails();
            return;
        }
        $planModel = Plan::where('name', $plan)->first();
        $user = User::where('chat_id', $this->chat_id())->first();

        if (!$planModel || !$user) {
            $id = $this->request['message']['id'];
            if (!is_null($id)) {
                Telegraph::chat($this->chat_id())
                    ->deleteMessage($id);
            }
            Telegraph::chat($this->chat_id())
                ->message('Something went wrong: plan or user not found.')
                ->send();
            return;
        }
        $card = Card::where('user_id', $user->id)->where('verified', true)->latest()->first();
        $keys = Keyboard::make()->buttons([
            Button::make('✅Tasdiqlash')->action('pay')->param('plan_id', $planModel->id)->width(1),
            Button::make("♻︎ Kartani o'zgartirish")->action('askForCardDetails')->width(0.8),
            Button::make("🧐Orqaga")->action('home')->width(0.2),
        ]);
        Telegraph::chat($this->chat_id())
            ->html("Obuna: ".$planModel->name."\nNarxi: ".$planModel->price / 100 ."\nKarta: ".$card->masked_number)
            ->keyboard($keys)
            ->send();
    }


    public function pay(string $plan_id): void
    {

        $plan = Plan::find($plan_id);
        $user = User::where('chat_id', $this->chat_id())->first();
        if ($user->hasActiveSubscription()) {
            Telegraph::chat($this->chat_id())
                ->message("Sizda obuna allaqachon faol!")
                ->send();
            return;
        }
        $this->callRecurrentPay($plan, $user);
    }



    protected function handleUnknownCommand(Stringable $text): void
    {
        $keyboard = ReplyKeyboard::make()
            ->row([
                ReplyButton::make("💳 To'lov"),
                ReplyButton::make("📋 Obuna holati"),
            ])->chunk(2)
            ->row([
                ReplyButton::make('🆘 Yordam'),
            ])->chunk(1)
            ->resize();
        Telegraph::chat($this->chat_id())
            ->message("🤷‍ Kechirasiz, bu buyruqni tushunmadim.")
            ->replyKeyboard($keyboard)
            ->send();

    }
    public function getStatus(): void
    {
        if (!$this->isRegistered($this->chat_id())) {
            $this->askForPhoneNumber();
        } else {
            $handler = new HandleChannel($this->getUser($this->chat_id()));
            $handler->getChannelUser();
        }
    }

}
