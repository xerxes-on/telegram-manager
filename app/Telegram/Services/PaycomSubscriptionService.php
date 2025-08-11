<?php
// app/Telegram/Services/PaycomSubscriptionService.php
namespace App\Telegram\Services;

use App\Enums\ConversationStates;
use App\Models\Card;
use App\Models\Client;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Receipt;
use App\Telegram\Services\ApiClients\PaycomApiClient;
use App\Telegram\Traits\CanAlterUsers;
use App\Telegram\Traits\CanCreateSubscription;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use Illuminate\Support\Facades\Cache;

class PaycomSubscriptionService extends WebhookHandler
{
    use CanAlterUsers, CanCreateSubscription;

    protected string $chat_id;
    protected PaycomApiClient $apiClient;

    public function __construct($chat_id)
    {
        parent::__construct();
        $this->chat_id = $chat_id;
        $this->apiClient = new PaycomApiClient(
            config('services.paycom.url'),
            config('services.paycom.id'),
            config('services.paycom.key'),
            function ($message) {
                $this->notify($message);
            }
        );
    }

    protected function notify(string|array $message): void
    {
        Telegraph::chat($this->chat_id)->html($message)->send();
    }

    public function cardsCreate(string $card, string $expire, Client $client): bool
    {
        $response = $this->apiClient->sendRequest('cards.create', [
            'card' => [
                'number' => $card,
                'expire' => $expire,
            ],
            'save' => true,
        ], true);

        if (isset($response['card'])) {
            $cardDetails = $response['card'];
            $card = $this->createCardRecord($client, $cardDetails);
            $success = $this->cardsSendVerifyCode($client, $cardDetails['token']);

            // If failed to send verification code, delete the card
            if (!$success && $card) {
                $card->delete();
            }

            return $success;
        } else {
            Cache::forget($this->chat_id . "card");
            // Set state back to waiting for card so user can try again
            $this->setState($client, ConversationStates::waiting_card);

            // Send error message and ask for card again
            if (isset($response['error'])) {
                $this->notify(__('telegram.card_error_try_again'));
            } else {
                $this->notify(__('telegram.card_add_failed'));
            }

            // Ask for card details again
            Telegraph::chat($this->chat_id)
                ->message(__('telegram.ask_for_card_number'))
                ->send();

            return false;
        }
    }

    protected function createCardRecord(Client $client, array $cardDetails): Card
    {
        return Card::query()->create([
            'client_id' => $client->id,
            'token' => $cardDetails['token'],
            'masked_number' => $cardDetails['number'],
            'expire' => $cardDetails['expire'],
            'phone' => $client->phone_number,
            'verified' => $cardDetails['verify'],
        ]);
    }

    public function cardsSendVerifyCode(Client $client, string $token): bool
    {
        $response = $this->apiClient->sendRequest('cards.get_verify_code', [
            'token' => $token,
        ], true);

        if ($response && isset($response['sent']) && $response['sent']) {
            $this->setState($client, ConversationStates::waiting_card_verify);
            $message = __('telegram.verification_code_sent', ['phone' => $response['phone']]);
            $this->notify($message);
            return true;
        } else {
            $this->notify(__('telegram.verification_code_send_failed'));
            return false;
        }
    }

    public function verifyCard(Card $card, int|string $code): bool
    {
        $response = $this->apiClient->sendRequest('cards.verify', [
            'token' => $card->token,
            'code' => $code,
        ], true);

        if (!$response) {
            $this->notify(__('telegram.incorrect_code'));
            // Delete unverified card on incorrect code
            $card->delete();
            return false;
        }

        $cardData = $response['card'] ?? null;
        if (!$cardData || !$cardData['verify']) {
            $this->notify(__('telegram.card_verification_unexpected_response'));
            // Delete unverified card on verification failure
            $card->delete();
            return false;
        }

        // Only update if verification is successful
        $card->update([
            'verified' => true,
        ]);
        $card->client->setMainCard($card);
        $this->notify(__('telegram.card_verified'));
        return true;
    }

    public function cardCheck(Client $client): bool
    {
        $card = $client->cards()->first();
        if (!$card) {
            $this->notify(__('telegram.card_not_found'));
            return false;
        }
        $response = $this->apiClient->sendRequest('cards.check', [
            'token' => $card->token,
        ], true);

        if (isset($response['card']) && $response['card']['verify']) {
            return true;
        }
        $this->notify(__('telegram.card_not_found'));
        Cache::forget($this->chat_id . "card");
        return false;
    }

    public function receiptsCreate(Plan $plan, Client $client, Order $order): void
    {
        $params = [
            'amount' => $plan->price,
            'account' => [
                'order_id' => $order->id,
            ],
            'description' =>  __('telegram.telegram_channel_subscription') . "-" . $plan->name,
            'detail' => [
                'receipt_type' => 0,
                'items' => [
                    'title' => __('telegram.telegram_channel_subscription') . "-" . $plan->name,
                    'price' => $plan->price,
                    'count' => 1,
                    'code' => config('services.tax.product_code'),
                    'package_code' => config('services.tax.package_code'),
                    'vat_percent' => (int) config('services.tax.vat_percent'),
                ],
            ],
        ];

        $response = $this->apiClient->sendRequest('receipts.create', $params, true);
        if (!$response || !isset($response['receipt'])) {
            return;
        }

        $receiptId = $response['receipt']['_id'];
        $this->createReceiptRecord($receiptId, $response['receipt']);

        $verifiedCard = $client->cards()->where('verified', true)->latest()->first();
        if ($verifiedCard) {
            $this->receiptsPay($receiptId, $verifiedCard->token, $order);
        } else {
            $this->notify(__('telegram.no_verified_card'));
        }
    }

    protected function createReceiptRecord(string $receiptId, array $meta)
    {
        return Receipt::query()->create([
            'receipt_id' => $receiptId,
            'metadata' => json_encode($meta)
        ]);
    }

    public function receiptsPay(string $receiptId, string $token, Order $order): void
    {
        $response = $this->apiClient->sendRequest('receipts.pay', [
            'id' => $receiptId,
            'token' => $token,
        ]);

        if (isset($response['receipt']) && $response['receipt']['state'] == 4) {
            $this->createSubscription($order->client, $order->plan, $receiptId);
            $this->receiptsSend($order->client, $receiptId);
        } else {
            $this->notify(__('telegram.payment_failed'));
        }
    }

    public function receiptsSend(Client $client, string $receiptId): void
    {
        $phone = substr($client->phone_number, 1);
        $this->apiClient->sendRequest('receipts.send', [
            'id' => $receiptId,
            'phone' => $phone,
        ]);
    }

    public function createFreePlan(Client $client, Plan $plan): void
    {
        $this->createSubscription($client, $plan, 'this_is_a_free_plan');
    }
}
