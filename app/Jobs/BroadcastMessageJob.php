<?php

namespace App\Jobs;

use App\Models\User;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class BroadcastMessageJob extends WebhookHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;
    protected string $messageContent;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $messageContent)
    {
        parent::__construct();
        $this->user = $user;
        $this->messageContent = $messageContent;
    }

    /**
     * Execute the job.
     * @param  Request  $request
     * @param  TelegraphBot  $bot
     */
    public function handle(Request $request, TelegraphBot $bot): void
    {
        Telegraph::chat($this->user->chat_id)
            ->markdownV2($this->messageContent)
            ->send();
    }
}
