<?php

namespace App\Telegram\Services\ApiClients;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

trait PaymeApiHelpers
{
    public PendingRequest $client;

    public function __construct(
        protected readonly string $login,
        protected readonly string $password,
        protected readonly string $deviceId,
        protected readonly string $deviceKey
    )
    {
        $this->client = Http::acceptJson()
            ->baseUrl(config('services.paycom.url', 'https://merchant.payme.uz/api'));
    }

    public function request(string $method, array $body = [])
    {
        $token = cache()->remember(
            'payme_api_token_' . $this->login,
            now()->addMinutes(5),
            function () {
                $response = $this->client->post('/users.login', [
                    'id' => time(),
                    'jsonrpc' => '2.0',
                    'method' => 'users.login',
                    'params' => [
                        'phone' => $this->login,
                        'password' => $this->password,
                        'trusted_device_key' => $this->deviceKey,
                        'trusted_device_id' => $this->deviceId
                    ]
                ]);

                $response->throw();

                if ($response->json('result.activation_required')) {
                    throw new Exception('Activation required for Payme API (OTP)');
                }

                return $response->json('result.token');
            });

        return $this->client
            ->withHeaders(['authentication' => 'Bearer ' . $token])
            ->post('/' . $method, [
                'id' => time(),
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $body
            ]);
    }
}