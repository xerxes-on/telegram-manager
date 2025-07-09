<?php

use App\Jobs\CheckSubscriptionsJob;
use App\Jobs\RenewSubscriptionsJob;
use App\Jobs\SendSubscriptionReminderJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(SendSubscriptionReminderJob::class)
    ->daily();
Schedule::job(CheckSubscriptionsJob::class)
    ->daily()->at('18:00');
Schedule::job(RenewSubscriptionsJob::class)
    ->twiceDaily()
    ->withoutOverlapping();
Schedule::command('queue:work --stop-when-empty')
    ->everyFiveMinutes()
    ->withoutOverlapping();
