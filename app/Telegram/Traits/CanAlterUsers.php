<?php

namespace App\Telegram\Traits;

use App\Enums\ConversationStates;
use App\Models\Client;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use JetBrains\PhpStorm\NoReturn;

trait CanAlterUsers
{

    #[NoReturn] public function askForPhoneNumber(): void
    {
        Telegraph::chat($this->chat->chat_id)
            ->message(__('telegram.ask_phone_number'))
            ->replyKeyboard(
                ReplyKeyboard::make()
                    ->button(__('telegram.send_phone_number_button'))
                    ->requestContact()
                    ->resize()
            )
            ->send();
        die();
    }

    public function setState(Client $client, ConversationStates $state): void
    {
        $client->update(['state' => $state]);
    }

    private function getCreateClient(): Client
    {
        $client = Client::query()->where('chat_id', $this->chat->chat_id)->first();
        if (!$client) {
            $telegramUser = $this->message->from();
            $client = Client::query()->create([
                'chat_id' => $this->chat->chat_id,
                'first_name' => $telegramUser->firstName(),
                'telegram_id' => $telegramUser->id(),
                'last_name' => $telegramUser->lastName(),
                'username' => $telegramUser->username(),
                'lang' => $telegramUser->languageCode(),
                'state' => ConversationStates::waiting_phone,
            ]);
            Telegraph::chat($this->chat->chat_id)
                ->reactWithEmoji($this->message->id(), '😇')
                ->send();
            $this->askForPhoneNumber();
        }

        return $client;
    }

    public function sendClientDetails(Client $client): void
    {
        Telegraph::chat($this->chat->chat_id)
            ->message(__('telegram.your_details', [
                'first_name' => $client->first_name,
                'phone_number' => $client->phone_number,
            ]))
            ->send();
    }
}
