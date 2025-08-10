<?php

namespace App\Enums;

enum ConversationStates: string
{
    case waiting_lang = 'waiting_for_lang';
    case waiting_phone = 'waiting_for_phone';
    case waiting_card = 'waiting_for_card';
    case waiting_card_expire = 'waiting_for_card_expire';
    case waiting_card_verify = 'waiting_for_verify';
    case chat = 'chat';
}
