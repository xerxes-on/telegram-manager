<?php

namespace App\Telegram\Traits;

use App\Models\User;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;

/**
 * Trait for handling user state (file-based) and checking registration.
 */
trait CanAlterUsers
{
    /**
     * Check if the user with given chat_id is already registered.
     *
     * @param  int  $chat_id
     * @return bool
     */
    public function isRegistered(int $chat_id): bool
    {
        return User::where('chat_id', $chat_id)->exists();
    }
    public function getUser(int $chat_id): User
    {
        return User::where('chat_id', $chat_id)->first();
    }

    private function setState(int $chatId, string $state, string $data = ''): void
    {
        $filePath = $this->getStateFilePath($chatId);
        $content  = json_encode(['state' => $state, 'data' => $data]);
        file_put_contents($filePath, $content);
    }

    /**
     * Retrieve a user's state from file.
     *
     * @param  int  $chatId
     * @return array{state: string|null, data: string|null}
     */
    private function getState(int $chatId): array
    {
        $filePath = $this->getStateFilePath($chatId);

        if (!file_exists($filePath)) {
            return ['state' => null, 'data' => null];
        }

        $content = file_get_contents($filePath);
        return json_decode($content, true) ?? ['state' => null, 'data' => null];
    }

    /**
     * Generate the storage file path for a given chat ID.
     *
     * @param  int  $chatId
     * @return string
     */
    private function getStateFilePath(int $chatId): string
    {
        return storage_path("telegram_states/{$chatId}.txt");
    }

    /**
     * Clear the user's state file.
     *
     * @param  int  $chatId
     * @return void
     */
    private function clearState(int $chatId): void
    {
        $filePath = $this->getStateFilePath($chatId);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * (Optional) You can add any helper method to send user data or
     * fetch user info, for instance:
     *
     * @param  int  $chatId
     * @return void
     */
    public function sendUserData(int $chatId): void
    {
        $user = User::where('chat_id', $chatId)->first();
        if ($user) {
            // You can modify this message to include any relevant user data
            \DefStudio\Telegraph\Facades\Telegraph::chat($chatId)
                ->message("Sizning ma'lumotlaringiz:\n".
                    "Ism: {$user->name}\n".
                    "Telefon: {$user->phone_number}")
                ->send();
        }
    }
    public function askForPhoneNumber(): void
    {
        $this->setState($this->chat_id(), 'waiting_for_phone');

        $keyboard = ReplyKeyboard::make()
            ->button('🫣Send Contact')->requestContact()->resize();
        Telegraph::chat($this->chat_id())
            ->message('Telefon raqamingizni yuboring :)')
            ->replyKeyboard($keyboard)
            ->send();
    }
}
