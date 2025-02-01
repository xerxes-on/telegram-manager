<?php
// app/Telegram/Services/PaycomApiClient.php
namespace App\Telegram\Services;

use Exception;
use Illuminate\Support\Facades\Http;

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
     * @param  string  $method
     * @param  array  $params
     * @param  bool  $front  Whether to use front-end credentials
     *
     * @return array|null
     */
    public function sendRequest(string $method, array $params, bool $front = false): ?array
    {
        $payload = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => uniqid(),
        ];

        try {
            $auth = $front ? $this->apiId : ($this->apiId.":".$this->apiKey);

            $response = Http::withHeaders([
                'x-auth' => $auth,
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, $payload);

            $data = $response->json();

            if (isset($data['error'])) {
                call_user_func($this->notify, $data['error']['message']);
            }

            if (isset($data['result'])) {
                return $data['result'];
            }

            call_user_func($this->notify, 'Unexpected error from Payme API');
        } catch (Exception $e) {
            call_user_func($this->notify, 'Error communicating with Payme API: '.$e->getMessage());
        }

        return null;
    }
}
