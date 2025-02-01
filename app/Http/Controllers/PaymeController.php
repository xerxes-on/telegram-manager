<?php

namespace App\Http\Controllers;

use App\Telegram\Services\PaymeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymeController extends Controller
{
    private PaymeService $paymeService;

    public function __construct(PaymeService $paymeService)
    {
        $this->paymeService = $paymeService;
    }

    /**
     * Single endpoint to handle all Payme JSON-RPC requests.
     *
     * @throws Exception
     */
    public function handlePaymeRequest(Request $request): JsonResponse
    {
        // JSON-RPC standard fields
        $method = $request->input('method');
        $params = $request->input('params', []);
        $requestId = $request->input('id');

        // Validate request
        if (empty($method)) {
            return $this->jsonRpcError($requestId, -32600, 'Invalid request: missing "method".');
        }

        // Validate 'account' key only for methods that require it
        if ($this->requiresAccountValidation($method)) {
            $accountValidation = $this->validateAccount($params);
            if ($accountValidation) {
                return $this->jsonRpcError($requestId, -32504, $accountValidation);
            }
        }

        // Dispatch to the appropriate method
        $responseData = $this->dispatchMethod($method, $params);

        // Return a JSON-RPC response
        return response()->json([
                'id' => $requestId,
            ] + $responseData);
    }

    /**
     * Helper to format JSON-RPC errors.
     */
    private function jsonRpcError($requestId, int $code, $message): JsonResponse
    {
        return response()->json([
            'id' => $requestId,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ]);
    }

    /**
     * Determine if the method requires account validation.
     */
    private function requiresAccountValidation(string $method): bool
    {
        // List of methods that require the 'account' key
        $methodsRequiringAccount = [
            'CheckPerformTransaction',
            'CreateTransaction',
        ];

        return in_array($method, $methodsRequiringAccount);
    }

    /**
     * Validate the 'account' key in the parameters.
     */
    private function validateAccount(array $params): ?array
    {
        if (!array_key_exists('account', $params) || empty($params['account'])) {
            return [
                'uz' => 'Bajarish usuli uchun imtiyozlar etarli emas',
                'ru' => 'Недостаточно привилегий для выполнения метода',
                'en' => 'Insufficient privileges to execute the method',
            ];
        }

        return null; // No errors
    }

    /**
     * Dispatch Payme methods to the appropriate service handler.
     */
    private function dispatchMethod(string $method, array $params): array
    {
        switch ($method) {
            case 'CheckPerformTransaction':
                return $this->paymeService->checkPerformTransaction($params);

            case 'CreateTransaction':
                return $this->paymeService->createTransaction($params);

            case 'CheckTransaction':
                return $this->paymeService->checkTransaction($params);

            case 'PerformTransaction':
                return $this->paymeService->performTransaction($params);

//            case 'CancelTransaction':
//                return $this->paymeService->cancelTransaction($params);

            case 'GetStatement':
                return $this->paymeService->getStatement($params);

            case 'ChangePassword':
                return $this->paymeService->changePassword();

            default:
                return $this->error(-32601, 'Method not found.');
        }
    }

    /**
     * Helper to format service errors.
     */
    private function error(int $code, $message): array
    {
        return [
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }
}
