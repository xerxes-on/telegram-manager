<?php

use App\Http\Controllers\PaymeController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use App\Jobs\SendSubscriptionReminderJob;
use App\Jobs\CheckSubscriptionsJob;
use App\Jobs\ProcessSubscriptionRenewalsJob;
use App\Jobs\RenewSubscriptionsJob;
use App\Jobs\SendSubscriptionRemindersJob;
use App\Jobs\BroadcastMessageJob;


Route::post('api/payme', [PaymeController::class, 'handlePaymeRequest'])
    ->withoutMiddleware(VerifyCsrfToken::class);

Route::get('/test-jobs', function () {
    $jobs = [];
    
    // 1. First send a reminder job
    SendSubscriptionRemindersJob::dispatch();
    $jobs[] = 'SendSubscriptionRemindersJob dispatched';
    
    // 2. Check subscriptions job
    CheckSubscriptionsJob::dispatch();
    $jobs[] = 'CheckSubscriptionsJob dispatched';
    
    // 3. Process subscription renewals job
    ProcessSubscriptionRenewalsJob::dispatch();
    $jobs[] = 'ProcessSubscriptionRenewalsJob dispatched';
    
    // 4. Renew subscriptions job
    RenewSubscriptionsJob::dispatch();
    $jobs[] = 'RenewSubscriptionsJob dispatched';
    
    // 5. Broadcast message job (if needed for testing)
    // BroadcastMessageJob::dispatch($message, $channelId);
    // $jobs[] = 'BroadcastMessageJob dispatched';
    
    return response()->json([
        'message' => 'All jobs dispatched successfully',
        'jobs' => $jobs,
        'timestamp' => now()->toDateTimeString()
    ]);
});
