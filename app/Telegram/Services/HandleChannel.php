<?php

namespace App\Telegram\Services;

use DefStudio\Telegraph\Facades\Telegraph;

class HandleChannel
{
    /**
     * Add a user to a specific Telegram channel.
     *
     * @param int $userId Telegram user ID
     * @param string $channelUsername Channel username (e.g., '@my_channel')
     * @return bool
     */
    public function addUserToChannel(int $userId, string $channelUsername): bool
    {
        try {
            Telegraph::bot('default')
                ->addChatMember($channelUsername, $userId)
                ->send();

            return true; // User successfully added
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error adding user to channel: ' . $e->getMessage());

            return false; // User addition failed
        }
    }

    /**
     * Remove a user from a specific Telegram channel.
     *
     * @param int $userId Telegram user ID
     * @param string $channelUsername Channel username (e.g., '@my_channel')
     * @return bool
     */
    public function removeUserFromChannel(int $userId, string $channelUsername): bool
    {
        try {
            Telegraph::bot()
                ->kickChatMember($channelUsername, $userId)
                ->send();

            return true; // User successfully removed
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error removing user from channel: ' . $e->getMessage());

            return false; // User removal failed
        }
    }
}
