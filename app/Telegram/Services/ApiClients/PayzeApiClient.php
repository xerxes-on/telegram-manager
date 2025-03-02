<?php

namespace App\Telegram\Services\ApiClients;

use App\Models\Plan;
use Illuminate\Support\Facades\Http;

class PayzeApiClient
{
    protected string $apiUrl;

    protected string $auth;
    protected string $webhookGateway;
    protected $notify;

    public function __construct(string $apiUrl, string $apiId, string $apiKey, string $webhookGateway, callable $notify)
    {
        $this->auth = $apiId.":".$apiKey;
        $this->apiUrl = $apiUrl;
        $this->notify = $notify;
        $this->webhookGateway = $webhookGateway;
    }

    public function sendRequest(Plan $plan, string $currency, string $phone_number, string|null $token): ?array
    {
        $price = (string) ($currency === 'USD' ? $plan->price_usd : $plan->price_uzs);
        $payload = [
            'source' => "Card",
            'amount' => $price,
            'language' => "UZ",
            "currency" => $currency,
            "hooks" => [
                "webhookGateway" => $this->webhookGateway,
                "successRedirectGateway" => $this->webhookGateway."/payze/gateway/success",
                "errorRedirectGateway" => $this->webhookGateway."/payze/gateway/success"
            ],
            "metadata" => [
                "order" => [
                    "orderId" => uniqid(),
                    "orderItems" => [
                        [
                            "productName" => "Telegram kanal obunasi - ".$plan->name,
                            "productCode" => (string) $plan->id,
                            "productQuantity" => 1,
                            "price" => $price,
                            "sumPrice" => $price
                        ]
                    ],
                    "billingAddress" => [
                        "phoneNumber" => $phone_number
                    ]
                ],
                "extraAttributes" => [
                    [
                        "key" => "RECEIPT_TYPE",
                        "value" => "Sale",
                        "description" => "OFD Receipt type"
                    ]
                ]
            ]
        ];
        if ($token) {
            $payload["token"] = $token;
        } else {
            $payload["cardPayment"] = ["tokenizeCard" => true];
        }
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->auth,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->put($this->apiUrl, $payload);

            $data = $response->json();
            if (!is_null($data['status']['message'])) {
                call_user_func($this->notify, $data['status']['message']);
            }
            if (!is_null($data['data'])) {
                return $data['data'];
            }
            call_user_func($this->notify, $data['status']['errors']);
        } catch (\Exception $e) {
            call_user_func($this->notify, 'Error communicating with Payze API: '.$e->getMessage());
        }

        return null;
    }


}
