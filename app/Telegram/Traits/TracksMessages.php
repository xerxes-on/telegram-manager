<?php

namespace App\Telegram\Traits;

use App\Telegram\Services\MessageTracker;
use DefStudio\Telegraph\Facades\Telegraph;

trait TracksMessages
{
    /**
     * Wrap Telegraph calls to track messages
     */
    protected function telegraph()
    {
        $handler = $this;
        
        return new class($this->chat->chat_id, $handler) {
            private $chatId;
            private $handler;
            private $telegraph;
            
            public function __construct($chatId, $handler)
            {
                $this->chatId = $chatId;
                $this->handler = $handler;
                $this->telegraph = Telegraph::chat($chatId);
            }
            
            public function __call($method, $args)
            {
                $this->telegraph = $this->telegraph->$method(...$args);
                return $this;
            }
            
            public function send()
            {
                $response = $this->telegraph->send();
                
                if ($response->successful() && isset($response->json()['result']['message_id'])) {
                    MessageTracker::trackMessage($this->chatId, $response->json()['result']['message_id']);
                }
                
                return $response;
            }
        };
    }
    /**
     * Send a message and track it
     */
    protected function sendMessage($text, $keyboard = null)
    {
        $telegraph = Telegraph::chat($this->chat->chat_id)->message($text);
        
        if ($keyboard) {
            $telegraph = $telegraph->keyboard($keyboard);
        }
        
        $response = $telegraph->send();
        
        // Track the message
        if ($response->successful() && isset($response->json()['result']['message_id'])) {
            MessageTracker::trackMessage($this->chat->chat_id, $response->json()['result']['message_id']);
        }
        
        return $response;
    }
    
    /**
     * Send HTML message and track it
     */
    protected function sendHtml($html, $keyboard = null)
    {
        $telegraph = Telegraph::chat($this->chat->chat_id)->html($html);
        
        if ($keyboard) {
            $telegraph = $telegraph->keyboard($keyboard);
        }
        
        $response = $telegraph->send();
        
        // Track the message
        if ($response->successful() && isset($response->json()['result']['message_id'])) {
            MessageTracker::trackMessage($this->chat->chat_id, $response->json()['result']['message_id']);
        }
        
        return $response;
    }
    
    /**
     * Reply to a message and track it
     */
    protected function replyMessage($text, $keyboard = null)
    {
        $telegraph = Telegraph::chat($this->chat->chat_id)
            ->message($text)
            ->reply($this->message->id());
        
        if ($keyboard) {
            $telegraph = $telegraph->keyboard($keyboard);
        }
        
        $response = $telegraph->send();
        
        // Track the message
        if ($response->successful() && isset($response->json()['result']['message_id'])) {
            MessageTracker::trackMessage($this->chat->chat_id, $response->json()['result']['message_id']);
        }
        
        return $response;
    }
}