<?php

namespace App\Telegram\Services;

use App\Models\Client;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HandleChannel
{
    protected string $token;
    protected string $channelId;
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->token = env('TELEGRAM_BOT_TOKEN', 'null');
        $this->channelId = env('CHANNEL_ID', 'null');
        $this->client = $client;
    }

    public function getChannelUser()
    {
        $response = Http::get("https://api.telegram.org/bot$this->token/getChatMember", [
            'chat_id' => $this->channelId,
            'user_id' => $this->client->telegram_id,
        ]);

        $result = $response->json();
        return $result['result']['status'] ?? 'unknown';
    }

    public function generateInviteLink(): void
    {
        // First, check if the user is already a member of the channel.
        $memberResponse = Http::get("https://api.telegram.org/bot$this->token/getChatMember", [
            'chat_id' => $this->channelId,
            'user_id' => $this->client->telegram_id,
        ]);

        $memberResult = $memberResponse->json();
        $status = $memberResult['result']['status'] ?? null;

        if (in_array($status, ['member', 'administrator', 'creator'])) {
            return;
        }

        $response = Http::post("https://api.telegram.org/bot$this->token/createChatInviteLink", [
            'chat_id' => $this->channelId,
            'member_limit' => 1,
            'expire_date' => now()->addDay()->timestamp,
        ]);

        $result = $response->json();
        $inviteLink = $result['result']['invite_link'] ?? null;

        if ($inviteLink) {
            Telegraph::chat($this->client->chat_id)->html($inviteLink)->send();
        } else {
            Telegraph::chat($this->client->chat_id)
                ->message(__('telegram.something_went_wrong_support'))
                ->send();
        }
    }

    public function kickUser(): void
    {
        $kickResponse = Http::post("https://api.telegram.org/bot$this->token/banChatMember", [
            'chat_id' => $this->channelId,
            'user_id' => $this->client->telegram_id,
            'revoke_messages' => true,
        ]);

        if ($kickResponse->ok() && ($kickResponse->json()['ok'] ?? false)) {
            Log::info("User kicked successfully!");
            $unbanResponse = Http::post("https://api.telegram.org/bot$this->token/unbanChatMember", [
                'chat_id' => $this->channelId,
                'user_id' => $this->client->telegram_id,
                'only_if_banned' => true,
            ]);

            if ($unbanResponse->ok() && ($unbanResponse->json()['ok'] ?? false)) {
                $sticker = "CAACAgIAAxkBAAEyQ1lnxCUfHvU7WgYiZc-xdnqTrlYmvgAC8wADVp29Cmob68TH-pb-NgQ";
                Telegraph::chat($this->client->chat_id)->sticker($sticker)->send();

                Telegraph::chat($this->client->chat_id)
                    ->message(__('telegram.user_kicked'))
                    ->send();
            } else {
                Log::info("Failed to unban user: " . ($unbanResponse->json()['description'] ?? 'Unknown error'));
            }
        } else {
            Log::alert("Failed to kick user: " . ($kickResponse->json()['description'] ?? 'Unknown error'));
        }
    }
}
