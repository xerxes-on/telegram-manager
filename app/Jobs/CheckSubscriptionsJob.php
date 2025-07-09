<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Telegram\Services\HandleChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $subscriptions = Subscription::query()
            ->where('status', 1)
            ->get();
        foreach ($subscriptions as $subscription) {
            /** @var Subscription $subscription */
            $user = $subscription->client;
            app()->setLocale($user->lang ?? 'uz');
            if ($subscription->expires_at < Carbon::now()) {
                $subscription->status = 0;
                $subscription->save();
                $handleChannel = new HandleChannel($user);
                if ($handleChannel->getChannelUser() !== 'unknown') {
                    $handleChannel->kickUser();
                }
            }
        }
    }
}
