<?php

namespace App\Telegram\Traits;

use App\Models\Subscription;
use App\Models\User;
use App\Telegram\Services\HandleChannel;
use DefStudio\Telegraph\Enums\ChatActions;
use DefStudio\Telegraph\Exceptions\TelegraphException;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

trait HandlesButtonActions
{
    public function processSupportButton(): void
    {
        Telegraph::chat($this->chat_id())
            ->message("🙌 Qo'llab quvvatlash uchun adminga murojaat qiling: @xerxeson")
            ->send();
    }

    /**
     * @throws TelegraphException
     */
    public function processSubscriptionStatusButton(): void
    {
        Telegraph::chat($this->chat_id())
            ->chatAction(ChatActions::CHOOSE_STICKER)
            ->send();

        $sub = Subscription::where('status', 1)
            ->where('user_id', User::where('chat_id', $this->chat_id())
                ->first()->id)
            ->first();
        if (empty($sub)) {
            Telegraph::chat($this->chat_id())
                ->message("Sizda faol obuna yo'q 🙁")
                ->send();
            return;
        }
        Telegraph::chat($this->chat_id())
            ->message("Obunangiz ".$sub->expires_at." gacha mavjud 🙃")
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make('❌ Bekor qilish')->action('confirmDeletion')
                ]))
            ->send();
    }

    public function home(): void
    {
//        $id = $this->request['message']['id'] - 1;
//        if (!is_null($id)) {
//            Telegraph::chat($this->chat_id())
//                ->deleteMessage($id)
//                ->send();
//        }
        $this->sendPlans();
    }

    public function confirmDeletion(): void
    {
        Telegraph::chat($this->chat_id())
            ->message("O'chirishni tasdiqlaysizmi 😞")
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make('❌')->action('getDefaultKeyboard')->width(0.2),
                    Button::make('✅')->action('cancelPlan')->width(0.8)
                ]))
            ->send();
    }

    public function cancelPlan(): void
    {
        $user = User::where('chat_id', $this->chat_id())->first();
        $service = new HandleChannel($user);
        $service->kickUser();
        $user->subscriptions()->latest()->delete();
    }

}
