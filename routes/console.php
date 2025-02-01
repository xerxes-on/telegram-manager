<?php

use App\Jobs\RenewSubscriptionsJob;
use App\Jobs\SendSubscriptionReminderJob;
use Illuminate\Support\Facades\Schedule;

//Artisan::command('inspire', function () {
//    $this->comment(Inspiring::quote());
//})->purpose('Display an inspiring quote')->hourly();
Schedule::job(SendSubscriptionReminderJob::class)
    ->daily();
Schedule::job(RenewSubscriptionsJob::class)
    ->hourly();

