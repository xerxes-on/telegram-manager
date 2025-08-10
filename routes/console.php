<?php

use App\Jobs\CheckSubscriptionsJob;
use App\Jobs\ProcessSubscriptionRenewalsJob;
use App\Jobs\SendSubscriptionRemindersJob;
use Illuminate\Support\Facades\Schedule;

// Send subscription expiration reminders
Schedule::job(SendSubscriptionRemindersJob::class)
    ->dailyAt('10:00')
    ->withoutOverlapping();

// Process subscription renewals and handle expired subscriptions
Schedule::job(ProcessSubscriptionRenewalsJob::class)
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// Keep existing check subscriptions job
Schedule::job(CheckSubscriptionsJob::class)
    ->daily()->at('18:00');

// Run queue worker in daemon mode managed by supervisor in production.
// Remove queue:work from scheduler to avoid spawning workers repeatedly.
