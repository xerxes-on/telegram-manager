<?php

namespace App\Http\Controllers;

use App\Telegram\Services\PayzePaymentService;
use Illuminate\Http\Request;

class PayzeCallbackController extends Controller
{
    public function handleCallback(Request $request, PayzePaymentService $payzeService): \Illuminate\Http\JsonResponse
    {
        // Validate or parse the payload
        $payload = $request->all();
        // You might want to verify signatures, check IP, etc.

        // Pass to the service
        $payzeService->handlePayzeCallback($payload);

        // Respond with HTTP 200 to acknowledge
        return response()->json(['status' => 'ok'], 200);
    }
}
