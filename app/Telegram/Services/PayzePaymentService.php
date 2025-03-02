<?php

namespace App\Telegram\Services;

use App\Models\CardToken;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use App\Telegram\Services\ApiClients\PayzeApiClient;
use App\Telegram\Traits\CanCreateSubscription;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;

class PayzePaymentService extends WebhookHandler
{
    use CanCreateSubscription;

    protected string $chat_id;
    protected PayzeApiClient $apiClient;
    protected string $apiUrl = 'https://payze.io/v2/api/payment';

    public function __construct($chat_id)
    {
        parent::__construct();
        $this->chat_id = $chat_id;
        $this->apiClient = new PayzeApiClient(
            $this->apiUrl,
            env('PAYZE_API_KEY'),
            env('PAYZE_API_SECRET'),
            env('APP_URL'),
            function ($message) {
                $this->notify($message);
            }
        );
    }

    protected function notify(string|array $message): void
    {
        Telegraph::chat($this->chat_id)->html($message)->send();
    }

    /**
     * @throws \Exception
     */
    public function saveCard(User $user, Plan $plan, string $currency): void
    {
        $responseData = $this->apiClient->sendRequest($plan, $currency, $user->phone_number, null);
        if (is_null($responseData)) {
            throw new \Exception();
        }
        $responseData['payment']['cardPayment']['token'] ?
            CardToken::create([
                'user_id' => $user->id,
                'token' => $responseData['payment']['cardPayment']['token'],
                'is_active' => false
            ]) : $this->notify('Error creating the card');
        $link = $responseData['payment']['paymentUrl'];
        $link ? $this->notify($link) : $this->notify('Error creating the payment link');
    }

    /**
     * @throws \Exception
     */
    public function recurrentPay(User $user, Plan $plan, string $currency, string $token): void
    {
        $response = $this->apiClient->sendRequest($plan, $currency, $user->phone_number, $token);
        if (is_null($response['payment']["transactionId"])) {
            throw new \Exception('Could not charge');
        }
        $transaction = Transaction::create([
            'owner_id' => $user->id,
            'transaction' => $response['payment']["transactionId"],
            'amount' => $plan->price
        ]);
        $this->createSubscription($plan, $transaction->id);
    }
}
