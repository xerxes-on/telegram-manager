<?php

namespace App\Jobs;

use App\Jobs\Interfaces\SubscriptionNotifier;
use App\Jobs\services\TelegramUserKicker;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSubscriptionReminderJob implements ShouldQueue
{
    use Queueable;

    private SubscriptionNotifier $notifier;
    private TelegramUserKicker $expiryHandler;

    public function __construct(
        SubscriptionNotifier $notifier,
        TelegramUserKicker   $expiryHandler
    )
    {
        $this->notifier = $notifier;
        $this->expiryHandler = $expiryHandler;
    }

    public function handle(): void
    {
        $today = Carbon::now();
        $threeDaysBefore = $today->copy()->addDays(3)->toDateString();
        $twelveDaysBefore = $today->copy()->addWeek()->toDateString();

        $subscriptions = Subscription::query()
            ->where('status', 1)
            ->whereDate('expires_at', $threeDaysBefore)
            ->orWhereDate('expires_at', $twelveDaysBefore)
            ->orWhere('expires_at', '<', $today->toDateString())
            ->get();

        foreach ($subscriptions as $subscription) {
            /** @var Subscription $subscription */
            $daysLeft = $subscription->expires_at->diffInDays($today);
            $client = $subscription->client;

            if ($daysLeft >= 0) {
                $this->notifier->notify($client, $daysLeft);
            } else {
                $this->expiryHandler->handle($client);
            }
        }
    }
}
