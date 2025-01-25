<?php

namespace App\Telegram\Traits;

use App\Models\User;
use DefStudio\Telegraph\Facades\Telegraph;

trait SendMessages
{
    public function send($message): void
    {
        $users = User::whereNotNull('chat_id')->get();

        foreach ($users as $user) {
            Telegraph::chat($user->chat_id)
                ->html($message->message)
                ->send();
        }
    }
}
