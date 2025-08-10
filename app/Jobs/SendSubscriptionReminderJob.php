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

    public function __construct()
    {
        // Dependencies will be resolved via container when handling
        $this->notifier = app(SubscriptionNotifier::class);
        $this->expiryHandler = app(TelegramUserKicker::class);
    }

    public function handle(): void
    {
        $today = Carbon::now();
        $threeDaysLater = $today->copy()->addDays(3)->toDateString();
        $sevenDaysLater = $today->copy()->addWeek()->toDateString();

        $subscriptions = Subscription::query()
            ->where('status', 1)
            ->where(function ($query) use ($threeDaysLater, $sevenDaysLater, $today) {
                $query->whereDate('expires_at', $threeDaysLater)
                    ->orWhereDate('expires_at', $sevenDaysLater)
                    ->orWhere('expires_at', '<', $today->toDateString());
            })
            ->chunk(100, function ($subscriptions) use ($today) {
                foreach ($subscriptions as $subscription) {
                    /** @var Subscription $subscription */
                    $daysLeft = $subscription->expires_at->isFuture() ? $subscription->expires_at->diffInDays($today) : 0;
                    $client = $subscription->client;

                    if ($subscription->expires_at >= $today) {
                        $this->notifier->notify($client, $daysLeft);
                    } else {
                        $this->expiryHandler->handle($client);
                    }
                }
            });
    }
}
