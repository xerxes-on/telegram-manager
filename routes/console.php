<?php

use App\Jobs\RenewSubscriptionsJob;
use App\Jobs\SendSubscriptionReminderJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(SendSubscriptionReminderJob::class)
    ->daily();
Schedule::job(RenewSubscriptionsJob::class)
    ->hourly()
    ->withoutOverlapping();
Schedule::command('queue:work --stop-when-empty')
    ->everyFiveMinutes()
    ->withoutOverlapping();
