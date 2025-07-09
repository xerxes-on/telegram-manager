<?php

namespace App\Telegram\Traits;

use App\Models\Card;
use App\Models\Client;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use JetBrains\PhpStorm\NoReturn;

trait CanHandleCards
{

    public function showMyCards(Client $client): void
    {
        $buttons = [];
        foreach ($client->cards as $card) {
            $buttons[] = Button::make($card->is_main ? '✅   ' . $card->masked_number : $card->masked_number)
                ->param('id', $card->id)
                ->action('setMainCard');
        }
        $buttons[] = Button::make(__('telegram.add_card_button'))
            ->action('addCardAction');
        Telegraph::chat($client->chat_id)
            ->message(__("telegram.choose_main_card"))
            ->keyboard(Keyboard::make()
                ->buttons($buttons))
            ->send();
    }
    public function setMainCard(string $id): void
    {
        Telegraph::chat($this->chat->chat_id)->deleteMessage($this->messageId)->send();
        $client = $this->getCreateClient();
        $card = Card::query()->find($id);
        if (!$card) {
            $this->askForCardDetails($client);
        }
        if(!$card->verified){
            $this->callVerifyToken($client, $card);
            return;
        }

        $client->setMainCard($card);
        Telegraph::chat($client->chat_id)
            ->html(__("telegram.card_set_main_success", ["card" => $card->masked_number]))
            ->send();
    }
    #[NoReturn] public function addCardAction(): void
    {
        Telegraph::chat($this->chat->chat_id)->deleteMessage($this->messageId)->send();
        $client = $this->getCreateClient();
        $this->askForCardDetails($client);
    }
}
