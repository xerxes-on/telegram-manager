<?php
// app/Telegram/Services/PaycomApiClient.php
namespace App\Telegram\Services\ApiClients;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaycomApiClient
{
    protected string $apiUrl;
    protected string $apiId;
    protected string $apiKey;
    /**
     * A callable to notify errors or messages.
     *
     * @var callable
     */
    protected $notify;

    public function __construct(string $apiUrl, string $apiId, string $apiKey, callable $notify)
    {
        $this->apiUrl = $apiUrl;
        $this->apiId = $apiId;
        $this->apiKey = $apiKey;
        $this->notify = $notify;
    }

    /**
     * Sends a JSON-RPC request to the Payme API.
     *
     * @param string $method
     * @param array $params
     * @param bool $front Whether to use front-end credentials
     *
     * @return array|null
     */
    public function sendRequest(string $method, array $params, bool $front = false): ?array
    {
        $payload = [
            'method' => $method,
            'params' => $params,
            'id' => hexdec(uniqid()),
        ];

        try {
            // x-auth header per Payme: if front=true send only merchant id, otherwise id:key
            $auth = $front ? $this->apiId : ($this->apiId . ":" . $this->apiKey);

            $response = Http::withHeaders([
                'x-auth' => $auth,
                'Cache-Control' => 'no-cache',
            ])->post($this->apiUrl, $payload);

            $data = $response->json();

            if (isset($data['error'])) {
                $errorMessage = is_array($data['error']['message']) ? json_encode($data['error']['message']) : $data['error']['message'];
                Log::warning($errorMessage);
                Log::info($data);
                throw new Exception($errorMessage . ' ' . ($data['error']['data']['message']['uz'] ?? ''));
            }

            if (isset($data['result'])) {
                return $data['result'];
            }

        } catch (Exception $e) {
            call_user_func($this->notify, $e->getMessage());
        }

        return null;
    }
}
