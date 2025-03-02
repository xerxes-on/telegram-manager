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

    private int $chatId;
    private ?User $user = null;

    public function chat_id(): int
    {
        if (!isset($this->chatId)) {
            $this->chatId = $this->chat->chat_id;
        }
        return $this->chatId;
    }

    private function getDefaultKeyboard(): ReplyKeyboard
    {
        return ReplyKeyboard::make()
            ->row([
                ReplyButton::make("💳 To'lov"),
                ReplyButton::make("📋 Obuna holati"),
            ])->chunk(2)
            ->row([
                ReplyButton::make('🆘 Yordam'),
            ])->chunk(1)
            ->resize();
    }

    /**
     * Returns the User model for the current chat, caching the result.
     */
    private function getUserModel(): ?User
    {
        if ($this->user === null) {
            $this->user = User::where('chat_id', $this->chat_id())->first();
        }
        return $this->user;
    }

    public function start(): void
    {
        $chatId = $this->chat_id();
        $messageId = $this->request['message']['message_id'] ?? null;

        Telegraph::chat($chatId)
            ->reactWithEmoji($messageId, '😇')
            ->send();

        Telegraph::chat($chatId)
            ->message(
                "Assalomu aleykum! Yangi \"Anvar Abduqayum Full Contact\" loyihamizga xush kelibsiz!\n\n".
                "Ushbu kanal uchun alohida vaqt ajratib zavq bilan yondashishga harakat qilaman!\n\n".
                "Bu kanalda:\n".
                "1. Faqat foydali content.\n".
                "Oylik obuna 500 000 so'm."
            )
            ->send();

        if ($this->isRegistered($chatId)) {
            $this->sendUserData($chatId);
            $this->sendPlans();
        } else {
            $this->askForPhoneNumber();
        }
    }

    /**
     * Handle incoming chat messages.
     *
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

        if ($message === "💳 To'lov") {
            $this->hasVerifiedCard() ? $this->sendPlans() : $this->askForCardDetails();
            return;
        }
        if ($message === '📋 Obuna holati') {
            $this->processSubscriptionStatusButton();
            return;
        }
        if ($message === "🆘 Yordam") {
            $this->processSupportButton();
            return;
        }

        // Handle messages based on current state
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

        Telegraph::chat($chatId)
            ->message("🤷‍ Kechirasiz, bu buyruqni tushunmadim.")
            ->replyKeyboard($this->getDefaultKeyboard())
            ->send();
    }

    private function processPhoneNumber(int $chatId, string $message): void
    {
        $contact = $this->request['message']['contact'] ?? null;
        $name = $this->request['message']['from']['first_name'] ?? "Anonymous";
        $messageId = $this->request['message']['message_id'] ?? null;

        // If sent as a contact, use that phone number
        if ($contact) {
            $phoneNumber = $contact['phone_number'] ?? null;
            if ($phoneNumber && preg_match('/^\+?[0-9]{10,15}$/', $phoneNumber)) {
                $this->acceptPhoneNumber($chatId, $phoneNumber, $name, $messageId);
                return;
            }
        }

        // Or, if the text message itself is a phone number:
        if (preg_match('/^\+?[0-9]{10,13}$/', $message)) {
            $this->acceptPhoneNumber($chatId, $message, $name, $messageId);
        } else {
            Telegraph::chat($chatId)
                ->message("Noto'g'ri telefon raqam. Iltimos, qayta urinib ko'ring yoki raqamni kontakt orqali yuboring.")
                ->send();
        }
    }

    /**
     * Handles a valid phone number by setting the state and prompting for the user’s name.
     */
    private function acceptPhoneNumber(int $chatId, string $phoneNumber, string $name, $messageId): void
    {
        $this->setState($chatId, 'waiting_for_name', $phoneNumber);
        Telegraph::chat($chatId)
            ->reactWithEmoji($messageId, '❤️')
            ->send();
        Telegraph::chat($chatId)
            ->message("Telefon raqamingiz qabul qilindi! Endi ismingizni yuboring:")
            ->replyKeyboard(ReplyKeyboard::make()->button($name)->resize())
            ->send();
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

        $messageId = $this->request['message']['message_id'] ?? null;
        Telegraph::chat($chatId)
            ->reactWithEmoji($messageId, '❤️')
            ->send();

        $this->clearState($chatId);

        Telegraph::chat($chatId)
            ->message("Rahmat, $message! Ma'lumotlaringiz saqlandi. 🎉")
            ->replyKeyboard($this->getDefaultKeyboard())
            ->send();

        $this->sendPlans();
    }

    public function savePlan(string $plan): void
    {
        $chatId = $this->chat_id();

        if (!$this->hasVerifiedCard()) {
            $this->askForCardDetails();
            return;
        }

        $planModel = Plan::where('name', $plan)->first();
        $user = $this->getUserModel();

        if (!$planModel || !$user) {
            $messageId = $this->request['message']['id'] ?? null;
            if (!is_null($messageId)) {
                Telegraph::chat($chatId)->deleteMessage($messageId);
            }
            Telegraph::chat($chatId)
                ->message('Something went wrong: plan or user not found.')
                ->send();
            return;
        }

        $card = Card::where('user_id', $user->id)
            ->where('verified', true)
            ->latest()
            ->first();

        $keys = Keyboard::make()->buttons([
            Button::make('✅Tasdiqlash')->action('choose')->param('plan_id', $planModel->id)->width(1),
            Button::make("♻︎ Kartani o'zgartirish")->action('askForCardDetails')->width(0.8),
            Button::make("🧐Orqaga")->action('home')->width(0.2),
        ]);

        Telegraph::chat($chatId)
            ->html("Obuna: ".$planModel->name."\nNarxi: ".($planModel->price / 100)."\nKarta: ".$card->masked_number)
            ->keyboard($keys)
            ->send();
    }

    public function choose(string $plan_id): void
    {
        $chatId = $this->chat_id();
        $plan = Plan::find($plan_id);
        $keys = Keyboard::make()->buttons([
            Button::make('💳 Uzcard/Humo')->action('payPayme')->param('plan_id', $plan->id)->width(0.5),
//            Button::make("💳 Visa/MasterCard")->action('payPayze')->param('plan_id', $plan->id)->width(0.5),
        ]);
        Telegraph::chat($chatId)
            ->html("Karta turini tanlang: ")
            ->keyboard($keys)
            ->send();
    }

    public function payPayme(string $plan_id): void
    {
        $chatId = $this->chat_id();
        $plan = Plan::find($plan_id);
        $user = $this->getUserModel();
        if ($user && $user->hasActiveSubscription()) {
            Telegraph::chat($chatId)
                ->message("Sizda obuna allaqachon faol!")
                ->send();
            return;
        }
        $this->callRecurrentPay($plan, $user);
    }

//    public function payPayze(string $plan_id): void
//    {
//        $chatId = $this->chat_id();
//        $plan = Plan::find($plan_id);
//        $user = $this->getUserModel();
//        if ($user && $user->hasActiveSubscription()) {
//            Telegraph::chat($chatId)
//                ->message("Sizda obuna allaqachon faol!")
//                ->send();
//            return;
//        }
//        $this->sendPayzePaymentLink($user, $plan, 'USD');
//    }

    protected function handleUnknownCommand(Stringable $text): void
    {
        Telegraph::chat($this->chat_id())
            ->message("🤷‍ Kechirasiz, bu buyruqni tushunmadim.")
            ->replyKeyboard($this->getDefaultKeyboard())
            ->send();
    }

    public function getStatus(): void
    {
        $chatId = $this->chat_id();
        if (!$this->isRegistered($chatId)) {
            $this->askForPhoneNumber();
        } else {
            $handler = new HandleChannel($this->getUserModel());
            $handler->getChannelUser();
        }
    }
}
