<?php

use App\Http\Controllers\PaymeController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;


Route::post('api/payme', [PaymeController::class, 'handlePaymeRequest'])
    ->withoutMiddleware(VerifyCsrfToken::class);
