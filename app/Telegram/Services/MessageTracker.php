<?php

namespace App\Telegram\Services;

use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MessageTracker
{
    private const MAX_MESSAGES = 4;
    private const CACHE_TTL = 86400; // 24 hours
    
    /**
     * Track a new message and delete old ones
     */
    public static function trackMessage($chatId, int $messageId): void
    {
        $key = "chat_messages_{$chatId}";
        $messages = Cache::get($key, []);
        
        // Add new message
        $messages[] = $messageId;
        
        // If we have more than MAX_MESSAGES, delete the oldest ones
        if (count($messages) > self::MAX_MESSAGES) {
            $toDelete = array_slice($messages, 0, count($messages) - self::MAX_MESSAGES);
            
            foreach ($toDelete as $oldMessageId) {
                try {
                    Telegraph::chat($chatId)->deleteMessage($oldMessageId)->send();
                    Log::debug("Deleted message {$oldMessageId} from chat {$chatId}");
                } catch (\Exception $e) {
                    Log::debug("Could not delete message {$oldMessageId}: " . $e->getMessage());
                }
            }
            
            // Keep only the last MAX_MESSAGES
            $messages = array_slice($messages, -self::MAX_MESSAGES);
        }
        
        Cache::put($key, array_values($messages), self::CACHE_TTL);
    }
    
    /**
     * Track user message and delete old messages
     */
    public static function onUserMessage($chatId, int $messageId): void
    {
        // Track the user message
        self::trackMessage($chatId, $messageId);
    }
    
    /**
     * Clean all messages in a chat
     */
    public static function cleanChat($chatId): void
    {
        $key = "chat_messages_{$chatId}";
        $messages = Cache::get($key, []);
        
        foreach ($messages as $messageId) {
            try {
                Telegraph::chat($chatId)->deleteMessage($messageId)->send();
            } catch (\Exception $e) {
                // Ignore errors
            }
        }
        
        Cache::forget($key);
    }
    
    /**
     * Get current message count
     */
    public static function getMessageCount($chatId): int
    {
        $key = "chat_messages_{$chatId}";
        $messages = Cache::get($key, []);
        return count($messages);
    }
}