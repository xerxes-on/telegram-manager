<?php

namespace App\Telegram;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Telegram\Services\PayzePaymentService;
use App\Telegram\Traits\CanAlterUsers;
use App\Telegram\Traits\CanPayzePay;
use App\Telegram\Traits\HandlesButtonActions;
use App\Telegram\Traits\HasPlans;
use DefStudio\Telegraph\Enums\ChatActions;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use Illuminate\Support\Stringable;

class Handler extends WebhookHandler
{
    use CanAlterUsers, HasPlans, HandlesButtonActions, CanPayzePay;

    public function chat_id(): int
    {
        return $this->chat->chat_id;
    }

    public function start(): void
    {
        Telegraph::chat($this->chat_id())
            ->reactWithEmoji($this->request['message']['message_id'], '🤗')->send();

        Telegraph::chat($this->chat_id())
            ->message(
                "Ассалому алайкум! Янги \"ISAEV Full Contact\" лойиҳамизга Хуш келибсиз!\n\n".
                "У канал обуначиларига ёнимда юрган шогирдга муносабат қилгандек ".
                "ёндашишга харакат қиламан!\n\n".
                "Бу каналда:\n".
                "1. Кўпроқ аудио ва видео постлар бўлади.\n".
                "2. Кунлик хаётимдан кўпроқ инсайтлар кўрсатиб бораман.\n".
                "3. Ора-орада ЁПИҚ Zoom-учрашувлар уюштириб турамиз!\n".
                "4. Бошқа қизиқ ва қиймат берувчи сюрпризлар!\n\n".
                "Ойлик обуна 500 000 сум."
            )
            ->send();

        // Check if user is already registered
        if ($this->isRegistered($this->chat_id())) {
            $this->sendUserData($this->chat_id());
            $this->sendPlans();
        } else {
            // If not registered, set state to request phone number
            $this->setState($this->chat_id(), 'waiting_for_phone');
            $this->askForPhoneNumber();
        }
    }

    public function handleChatMessage(Stringable $text): void
    {
        $chatId = $this->chat_id();
        $stateArr = $this->getState($chatId);
        $state = $stateArr['state'] ?? null;
        $data = $stateArr['data'] ?? null;

        $message = trim($text); // Normalize spaces/newlines
        Telegraph::chat($chatId)->chatAction(ChatActions::TYPING)->send();

        switch ($message) {
            case '💳 Тўлов':
                $this->sendPlans();
                return;

            case '📋 Обуна ҳолати':
                $this->processSubscriptionStatusButton();
                return;

            case '🆘 Қўллаб-қувватлаш':
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
        }

        Telegraph::chat($chatId)
            ->message("🤷‍ Kechirasiz, bu buyruqni tushunmadim.")
            ->send();
    }

    /**
     * If the user typed an unknown command, handle it here.
     *
     * @param  Stringable  $text
     */
    protected function handleUnknownCommand(Stringable $text): void
    {
        $keyboard = ReplyKeyboard::make()
            ->button('💳 Тўлов')
            ->button('📋 Обуна ҳолати')
            ->button('🆘 Қўллаб-қувватлаш')
            ->chunk(3)
            ->resize();
        Telegraph::chat($this->chat_id())
            ->message("🤷‍ Kechirasiz, bu buyruqni tushunmadim.")
            ->replyKeyboard($keyboard)
            ->send();

    }

    public function savePlan(string $plan): void
    {
        $planModel = Plan::where('name', $plan)->first();
        $user = User::where('chat_id', $this->chat_id())->first();

        if (!$planModel || !$user) {
            Telegraph::chat($this->chat_id())
                ->message('Something went wrong: plan or user not found.')
                ->send();
            return;
        }

        // Check if there's already an existing "created" order for this plan
        $existingOrder = $user->orders
            ->where('plan_id', $planModel->id)
            ->where('status', 'created')
            ->first();

        if (empty($existingOrder)) {
            $existingOrder = Order::create([
                'price' => $planModel->price,
                'plan_id' => $planModel->id,
                'status' => 'created',
                'user_id' => $user->id
            ]);
        }
        Telegraph::chat($this->chat_id())
            ->message('To\'lov sahifasi
                 '.route('process.payment', [
                    'chatId' => $this->chat_id(),
                    'orderId' => $existingOrder->id
                ]))
            ->send();
//        $this->processPaymentOneTime($planModel, $user);
//        $this->sendChannelLink();
    }

    private function sendChannelLink(): void
    {
//        $signedUrl = URL::temporarySignedRoute('share-link', now()->addMinutes(5), [
//            'chat_id' => $this->chat_id(),
//        ]);
        $signedUrl = env('TELEGRAM_CHANNEL_LINK');
        $linkMessage = "<a href='{$signedUrl}' style='font-style: normal'>✨ Kanal uchun maxsus link</a>";

        Telegraph::chat($this->chat_id())
            ->html($linkMessage)
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

        User::create([
            'chat_id' => $chatId,
            'phone_number' => $phoneNumber,
            'name' => $message,
        ]);
        Telegraph::chat($chatId)
            ->reactWithEmoji($this->request['message']['message_id'], '❤️')
            ->send();
        $this->clearState($chatId);

        Telegraph::chat($chatId)
            ->message("Rahmat, {$message}! Ma'lumotlaringiz saqlandi. 🎉")
            ->replyKeyboard(ReplyKeyboard::make()
                ->button('💳 Тўлов')
                ->button('📋 Обуна ҳолати')
                ->button('🆘 Қўллаб-қувватлаш')
                ->chunk(3)->resize())
            ->send();

        $this->sendPlans();
    }

    public function handleSuccessfulPayment(User $user, float $amount, string $currency): void
    {
        $payzeService = app(PayzePaymentService::class);
        $payzeService->handleSuccessfulOneTimePayment($user, $amount, $currency);
    }


    public function pay(): void
    {
        $user = User::where('chat_id', $this->chat_id())->first();

        if (!$user) {
            Telegraph::chat($this->chat_id())
                ->message("Фойдаланувчи топилмади.")
                ->send();
            return;
        }

        if (!$user->cards()->exists()) {
            Telegraph::chat($this->chat_id())
                ->message("Карта маълумотлари топилмади.")
                ->send();
            return;
        }

        $activeSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->first();

        if ($activeSubscription) {
            Telegraph::chat($this->chat_id())
                ->message("Сизда аллақачон актив обуна мавжуд.")
                ->send();
            return;
        }

        $order = Order::where('user_id', $user->id)
            ->whereIn('status', ['created', 'pending'])
            ->first();

        if ($order) {
            $paymentLink = $this->generatePaymentLink($order);

            Telegraph::chat($this->chat_id())
                ->message("Тўлов линкини босинг: $paymentLink")
                ->send();
        } else {
            Telegraph::chat($this->chat_id())
                ->message("Активный план топилмади.")
                ->send();
        }
    }

    private function generatePaymentLink(Order $order): string
    {
        return "https://payment.example.com/order/{$order->id}";
    }
}
