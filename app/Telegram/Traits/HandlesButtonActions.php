<?php

namespace App\Telegram\Traits;

use DefStudio\Telegraph\Facades\Telegraph;

trait HandlesButtonActions
{
    /**
     * Handle the "💳 Тўлов" button.
     */
    public function processPaymentButton(): void
    {
        Telegraph::chat($this->chat_id())
            ->message("Тўловни амалга ошириш учун маълумотларингизни киритинг.")
            ->send();
    }


    /**
     * Handle the "📋 Обуна ҳолати" button.
     */
    public function processSubscriptionStatusButton(): void
    {
        Telegraph::chat($this->chat_id())
            ->message("Сизнинг обуна ҳолатингиз: [маълумот]")
            ->send();
    }

    /**
     * Handle the "🆘 Қўллаб-қувватлаш" button.
     */
    public function processSupportButton(): void
    {
        Telegraph::chat($this->chat_id())
            ->message("Қўллаб-қувватлаш учун бизга мурожаат қилинг: support@example.com")
            ->send();
    }
}
