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
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
            return $this->jsonRpcError($request->input('id'), -32504, [
                'uz' => 'Avtorizatsiya sarlavhasi topilmadi yoki noto\'g\'ri formatda.',
                'ru' => 'Заголовок авторизации не найден или имеет неправильный формат.',
                'en' => 'Authorization header not found or in wrong format.',
            ]);
        }

        $base64Credentials = substr($authHeader, 6);
        $decodedCredentials = base64_decode($base64Credentials);

        $parts = explode(':', $decodedCredentials, 2);

        if (count($parts) !== 2) {
            return $this->jsonRpcError($request->input('id'), -32504, [
                'uz' => 'Noto\'g\'ri avtorizatsiya ma\'lumotlari.',
                'ru' => 'Неверные данные авторизации.',
                'en' => 'Invalid authorization credentials.',
            ]);
        }

//        $username = $parts[0];
//        $password = $parts[1];
//
//        // !!! IMPORTANT: Replace 'YOUR_PAYCOM_API_KEY' with your actual Paycom API Key !!!
//        // You should store this key securely, e.g., in your .env file
//        $expectedUsername = 'Paycom'; // As per Paycom documentation for Basic Auth
//        $expectedPassword = env('PAYCOM_API_KEY'); // Get from .env file
//
//        if ($username !== $expectedUsername || $password !== $expectedPassword) {
//            return $this->jsonRpcError($request->input('id'), -32504, [ // Insufficient privileges
//                'uz' => 'Login yoki parol noto\'g\'ri.',
//                'ru' => 'Неверный логин или пароль.',
//                'en' => 'Incorrect login or password.',
//            ]);
//        }
//        // --- END AUTHENTICATION CHECK ---


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
        return match ($method) {
            'CheckPerformTransaction' => $this->paymeService->checkPerformTransaction($params),
            'CreateTransaction' => $this->paymeService->createTransaction($params),
            'CheckTransaction' => $this->paymeService->checkTransaction($params),
            'PerformTransaction' => $this->paymeService->performTransaction($params),
            'GetStatement' => $this->paymeService->getStatement($params),
            'ChangePassword' => $this->paymeService->changePassword(),
            default => $this->error(-32601),
        };
    }

    /**
     * Helper to format service errors.
     */
    private function error(int $code): array
    {
        return [
            'error' => [
                'code' => $code,
                'message' => 'Method not found.',
            ],
        ];
    }
}
