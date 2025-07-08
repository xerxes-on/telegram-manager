<?php

namespace App\Jobs\services;

use App\Models\Client;
use App\Telegram\Services\HandleChannel;

class TelegramUserKicker
{
    public function handle(Client $client): void
    {
        $handleChannel = new HandleChannel($client);
        $handleChannel->kickUser();
    }
}
