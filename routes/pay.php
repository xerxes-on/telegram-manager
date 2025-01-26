<?php

use App\Http\Controllers\Payments\PaymeController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::post('api/payments/payme', [PaymeController::class, 'handlePaymeRequest'])
    ->withoutMiddleware(VerifyCsrfToken::class);
