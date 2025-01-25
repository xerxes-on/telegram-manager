<?php

namespace App\Telegram;

use Illuminate\Support\Facades\Session;

class RegisterUser
{
    public int $chat_id;

    public function phone_number($chat_id): void
    {

        Session::put("registration.{$chat_id}.name",);
    }
}
