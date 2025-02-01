<?php

use App\Http\Controllers\PaymeController;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::post('api/payments/payme', [PaymeController::class, 'handlePaymeRequest'])
    ->withoutMiddleware(VerifyCsrfToken::class);
Route::get('/process-payment/{chatId}/{orderId}', function ($chatId, $orderId) {
    $user = User::where('chat_id', $chatId)->first();
    $order = Order::find($orderId);
    if (empty($user) || empty($order)) {
        abort(404);
    }
    if ($user->id != $order->user_id || $order->status != 'created') {
        abort(404);
    }
    $amount = $order->plan->price;
    return view('paymentPage', compact('orderId', 'amount'));
})->name('process.payment');
