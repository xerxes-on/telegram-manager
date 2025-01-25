<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use App\Jobs\BroadcastMessageJob;
use App\Models\Message;
use App\Models\User;
use App\Telegram\Traits\SendMessages;
use Filament\Resources\Pages\CreateRecord;

class CreateMessage extends CreateRecord
{

    protected static string $resource = MessageResource::class;

    protected function afterCreate(): void
    {
        $message = $this->record;

        $users = User::whereNotNull('chat_id')->get();

        foreach ($users as $user) {
            BroadcastMessageJob::dispatch($user, $message->message);
        }


        $message->update(['sent' => true]);

    }
}
