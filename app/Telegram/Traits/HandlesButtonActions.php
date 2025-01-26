<?php

namespace App\Telegram\Traits;

use DefStudio\Telegraph\Facades\Telegraph;

trait HandlesButtonActions
{
    public function processSupportButton(): void
    {
        Telegraph::chat($this->chat_id())
            ->message("🙌 Қўллаб-қувватлаш учун бизга мурожаат қилинг: @xerxeson")
            ->send();
    }
}
