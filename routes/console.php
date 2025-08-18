<?php

use App\Jobs\CheckSubscriptionsJob;
use App\Jobs\ProcessSubscriptionRenewalsJob;
use App\Jobs\SendSubscriptionRemindersJob;
use Illuminate\Support\Facades\Schedule;

// Send subscription expiration reminders
Schedule::job(SendSubscriptionRemindersJob::class)
    ->dailyAt('01:00')
    ->withoutOverlapping();

// Process subscription renewals and handle expired subscriptions
Schedule::job(ProcessSubscriptionRenewalsJob::class)
    ->everyFourHours()
    ->withoutOverlapping();

// Keep existing check subscriptions job
Schedule::job(CheckSubscriptionsJob::class)
    ->daily()->at('23:00');

Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();
