<?php
// app/Telegram/Services/PaycomSubscriptionService.php
namespace App\Telegram\Services;

use App\Models\Card;
use App\Models\Plan;
use App\Models\Receipt;
use App\Models\Subscription;
use App\Models\User;
use App\Telegram\Services\ApiClients\PaycomApiClient;
use App\Telegram\Traits\CanAlterUsers;
use App\Telegram\Traits\CanCreateSubscription;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use Illuminate\Support\Facades\Cache;

class PaycomSubscriptionService extends WebhookHandler
{
    use CanAlterUsers, CanCreateSubscription;

    protected string $admin_chat_id;
    protected string $chat_id;
    protected PaycomApiClient $apiClient;
    protected string $apiUrl = 'https://checkout.test.paycom.uz/api';

    public function __construct($chat_id)
    {
        parent::__construct();
        $this->chat_id = $chat_id;
        $this->apiClient = new PaycomApiClient(
            $this->apiUrl,
            env('PAYME_API_ID'),
            env('PAYME_API_KEY'),
            function ($message) {
                $this->notify($message);
            }
        );
    }

    protected function notify(string|array $message): void
    {
        Telegraph::chat($this->chat_id)->html($message)->send();
    }

    public function cardsCreate(string $card, string $expire, User $user): bool
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
            $this->createCardRecord($user, $cardDetails);
            return $this->cardsSendVerifyCode($cardDetails['token']);
        } else {
            $this->notify("Noma'lum karta");
            Cache::forget($this->chat_id."card");
            return false;
        }
    }

    protected function createCardRecord(User $user, array $cardDetails): Card
    {
        return Card::create([
            'user_id' => $user->id,
            'token' => $cardDetails['token'],
            'masked_number' => $cardDetails['number'],
            'expire' => $cardDetails['expire'],
            'phone' => $user->phone_number,
            'verified' => $cardDetails['verify'],
        ]);
    }

    public function cardsSendVerifyCode(string $token): bool
    {
        $response = $this->apiClient->sendRequest('cards.get_verify_code', [
            'token' => $token,
        ], true);

        if ($response && isset($response['sent']) && $response['sent']) {
            $this->setState($this->chat_id, 'waiting_for_verify');
            $message = "📲Kod +".$response['phone']." raqamingizga yuborildi!\nKodni kiriting:";
            $this->notify($message);
            return true;
        } else {
            $this->notify("📲Kod jo'natish o'xshamadi");
            return false;
        }
    }

    public function verifyCard(string $token, int|string $code): bool
    {
        $response = $this->apiClient->sendRequest('cards.verify', [
            'token' => $token,
            'code' => $code,
        ], true);

        if (!$response) {
            $this->notify("Notog'ri kod Iltimos yana harakat qilib koring.");
            return false;
        }

        $cardData = $response['card'] ?? null;
        if (!$cardData) {
            $this->notify("Unexpected response during card verification.");
            return false;
        }

        $card = Card::where('token', $token)->first();
        if ($card) {
            $card->update([
                'verified' => $cardData['verify'],
            ]);
        }
        $this->notify("Kartangiz Payme tomonidan tasdiqlandi.");
        return true;
    }

    public function cardCheck(User $user): bool
    {
        $card = $user->cards()->first();
        if (!$card) {
            $this->notify('Karta topilmadi');
            return false;
        }
        $response = $this->apiClient->sendRequest('cards.check', [
            'token' => $card->token,
        ], true);

        if (isset($response['card']) && $response['card']['verify']) {
            return true;
        }
        $this->notify('Karta topilmadi');
        Cache::forget($this->chat_id."card");
        return false;
    }

    public function receiptsCreate(Plan $plan, User $user): void
    {
        $params = [
            'amount' => $plan->price,
            'account' => [
                'phone_number' => $user->phone_number,
            ],
            'detail' => [
                'receipt_type' => 0,
                'items' => [
                    'title' => "Telegram channel subscription-".$plan->name,
                    'price' => $plan->price,
                    'count' => 1,
                    'code' => "10306013001000000", // from Soliq
                    'package_code' => "package_code",// from Soliq
                    'vat_percent' => 4,
                ],
            ],
        ];

        $response = $this->apiClient->sendRequest('receipts.create', $params, true);
        if (!$response || !isset($response['receipt'])) {
            return;
        }

        $receiptId = $response['receipt']['_id'];
        $this->createReceiptRecord($receiptId, $response['receipt']);

        $verifiedCard = $user->cards()->where('verified', true)->latest()->first();
        if ($verifiedCard) {
            $this->receiptsPay($receiptId, $verifiedCard->token, $plan);
        } else {
            $this->notify("No verified card found for payment.");
        }
    }

    protected function createReceiptRecord(string $receiptId, array $meta)
    {
        return Receipt::create([
            'receipt_id' => $receiptId,
            'metadata' => json_encode($meta)
        ]);
    }

    public function receiptsPay(string $receiptId, string $token, Plan $plan): void
    {
        $response = $this->apiClient->sendRequest('receipts.pay', [
            'id' => $receiptId,
            'token' => $token,
        ]);

        if (isset($response['receipt']) && $response['receipt']['state'] == 4) {
            $this->createSubscription($plan, $receiptId);
            $this->receiptsSend($receiptId);
        } else {
            $this->notify("To'lov amalga oshmadi");
        }
    }
    public function receiptsSend(string $receiptId): void
    {
        $user = User::where('chat_id', $this->chat_id)->first();
        $phone = substr($user->phone_number, 1);
        $this->apiClient->sendRequest('receipts.send', [
            'id' => $receiptId,
            'phone' => $phone,
        ]);
    }
    public function createFreePlan(Plan $plan): void
    {
        $this->createSubscription($plan, 'this_is_a_free_plan');
    }
}
