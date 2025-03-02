<?php

namespace App\Http\Controllers;

use App\Telegram\Services\PayzePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayzeCallbackController extends Controller
{
    public function success(Request $request, PayzePaymentService $payzeService): JsonResponse
    {
        $payload = $request->all();
        \Log::debug($payload);

//        $payzeService->handlePayzeCallback($payload);

        return response()->json(['status' => 'ok'], 200);
    }
    public function error(Request $request, PayzePaymentService $payzeService): JsonResponse
    {
        $payload = $request->all();
        \Log::debug($payload);

//        $payzeService->handlePayzeCallback($payload);

        return response()->json(['status' => 'ok'], 200);
    }
}
