<?php

namespace App\Telegram\Services;

use App\Models\User;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HandleChannel
{
    protected string $token;
    protected string $channelId;
    protected object $user;

    public function __construct(User $user)
    {
        $this->token = env('TELEGRAM_BOT_TOKEN');
        $this->channelId = env('CHANNEL_ID');
        $this->user = $user;
    }

    public function getChannelUser(): void
    {
        $response = Http::get("https://api.telegram.org/bot".$this->token."/getChatMember", [
            'chat_id' => $this->channelId,
            'user_id' => $this->user->user_id,
        ]);

        $result = $response->json();

        $status = $result['result']['status'];
        Telegraph::chat($this->user->chat_id)->message($status)->send();
    }

    public function generateInviteLink(): void
    {
        $response = Http::post("https://api.telegram.org/bot".$this->token."/createChatInviteLink", [
            'chat_id' => $this->channelId,
            'member_limit' => 1,
            'expire_date' => now()->addDay()->timestamp,
        ]);
        $result = $response->json();
        $inviteLink = $result['result']['invite_link'];
        if ($inviteLink) {
            Telegraph::chat($this->user->chat_id)->html($inviteLink)->send();
        } else {
            Telegraph::chat($this->user->chat_id)->message("Aah nimadir o'xshamadi :(\n Qo'llab-quvvatlashga murojaat qiling 🙃")->send();
        }
    }

    public function kickUser(): void
    {
        $kickResponse = Http::post("https://api.telegram.org/bot".$this->token."/banChatMember", [
            'chat_id' => $this->channelId,
            'user_id' => $this->user->user_id,
            'revoke_messages' => true,
        ]);

        if ($kickResponse->ok() && $kickResponse->json()['ok']) {
            Log::info("User kicked successfully!");
            sleep(10);
            $unbanResponse = Http::post("https://api.telegram.org/bot".$this->token."/unbanChatMember", [
                'chat_id' => $this->channelId,
                'user_id' => $this->user->user_id,
                'only_if_banned' => true,
            ]);

            if ($unbanResponse->ok() && $unbanResponse->json()['ok']) {
                $sticker = "CAACAgIAAxkBAAExQ3JnmzHSzshIUs2brFvaukLwJ3otPAACjg4AAjQCWEhEXiZTgoIOajYE";
                Telegraph::chat($this->user->chat_id)
                    ->sticker($sticker)->send();

                Telegraph::chat($this->user->chat_id)->message("Siz kanaldan chiqarib yuborildingiz\nIltimos obuna bo'ling, sizni sog'inamiz😢")->send();
            } else {
                Log::info("Failed to unban user: ".($unbanResponse->json()['description'] ?? 'Unknown error'));
            }
        } else {
            Log::alert("Failed to kick user: ".($kickResponse->json()['description'] ?? 'Unknown error'));
        }
    }
}
