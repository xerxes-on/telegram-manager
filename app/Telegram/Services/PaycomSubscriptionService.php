<?php
// app/Telegram/Services/PaycomSubscriptionService.php
namespace App\Telegram\Services;

use App\Models\Card;
use App\Models\Plan;
use App\Models\Receipt;
use App\Models\Subscription;
use App\Models\User;
use App\Telegram\Traits\CanAlterUsers;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use Illuminate\Support\Facades\Http;

class PaycomSubscriptionService extends WebhookHandler
{
    use CanAlterUsers;

    protected string $admin_chat_id;
    protected string $apiUrl = 'https://checkout.test.paycom.uz/api';
    protected string $chat_id;

    public function __construct($chat_id)
    {
        parent::__construct();
        $this->chat_id = $chat_id;
    }

    private function sendRequest(string $method, array $params, bool $front = false): array|null
    {
        $payload = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => uniqid(),
        ];
        try {
            $auth = $front ? env('PAYME_API_ID'):env('PAYME_API_ID').":".env('PAYME_API_KEY');
            $response = Http::withHeaders([
                'x-auth' => $auth,
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, $payload);
            $data = $response->json();
            if (isset($data['error'])) {
                $this->notify($data['error']['message']);
            }
            if (isset($data['result'])) {
                return $data['result'];
            }
            $this->notify('Unexpected error from Payme API');
        } catch (\Exception $e) {
            $this->notify('Error communicating with Payme API: '.$e->getMessage());
        }
        return null;
    }

    public function cardsCreate(string $card, string $expire, User $user): bool
    {
        $response = $this->sendRequest('cards.create', [
            'card' => [
                'number' => $card,
                'expire' => $expire,
            ],
            'save' => true,
        ], true);
        if (isset($response['card'])) {
            $cardDetails = $response['card'];
            Card::create([
                'user_id' => $user->id,
                'token' => $cardDetails['token'],
                'masked_number' => $cardDetails['number'],
                'expire' => $cardDetails['expire'],
                'phone' => $user->phone_number,
                'verified' => $cardDetails['verify']
            ]);
            return $this->cardsSendVerifyCode($response['card']['token']);
        } else {
            $this->notify("Noma'lum karta");
            return false;
        }
    }

    public function cardsSendVerifyCode(string $token): bool
    {
        $response = $this->sendRequest('cards.get_verify_code', [
            'token' => $token
        ], true);
        if ($response['sent']) {
            $this->setState($this->chat_id, 'waiting_for_verify');
            $message = "📲Kod +".$response['phone']." raqamingizga yuborildi!\nKodni kiriting:";
            $this->notify($message);
            return true;
        } else {
            $message = "📲Kod jo'natish o'xshamadi";
            $this->notify($message);
            return false;
        }
    }

    public function verifyCard(string $token, int|string $code): bool
    {
        $response = $this->sendRequest('cards.verify', [
            'token' => $token,
            'code' => $code,
        ], true);

        if (isset($response['error'])) {
            $message = "Notog'ri kod Iltimos yana harakat qilib koring.";
            $state = false;
        } else {
            $cardData = $response['card'];
            $card = Card::where('token', $token)->first();
            $card->update([
                'verified' => $cardData['verify']
            ]);
            $message = "Kartangiz Payme tomonidan tasdiqlandi.";
            $state = true;
        }
        $this->notify($message);
        return $state;
    }

    public function cardCheck(User $user): bool
    {
        $card = $user->cards()->first();
        $token = $card->token;
        $response = $this->sendRequest('cards.check', [
            'token' => $token,
        ], true);
        if (!$response['card']) {
            $this->notify('Karta topilamdi');
        }
        if ($response['card']['verify']) {
            return true;
        }
        return false;
    }

//    public function recurrentPay(array $params): array
//    {
//        $token = $params['token'] ?? null;
//        $amount = $params['amount'] ?? null;
//        $orderId = $params['account']['order_id'] ?? null;
//
//// Validate inputs
//        if (!$token || !$amount || !$orderId) {
//            return $this->error(-32600, 'Invalid parameters');
//        }
//
//// Retrieve card and order
//        $card = Card::where('token', $token)->first();
//        $order = Order::find($orderId);
//
//        if (!$card) {
//            return $this->error(-31003, 'Card not found');
//        }
//        if (!$order) {
//            return $this->error(-31050, 'Order not found');
//        }
//        if ($order->price != $amount) {
//            return $this->error(-31001, 'Incorrect amount');
//        }
//
//// Create transaction
//        $transaction = Transaction::create([
//            'paycom_transaction_id' => Str::uuid(),
//            'amount' => $amount,
//            'state' => 2,
//            'order_id' => $order->id,
//            'perform_time_unix' => now()->timestamp * 1000,
//        ]);
//
//// Update order and create subscription
//        $order->update(['status' => 'charged']);
//        $this->createSubscription($order);
//
//        return [
//            'result' => [
//                'transaction' => (string) $transaction->id,
//                'perform_time' => $transaction->perform_time_unix,
//                'state' => $transaction->state
//            ]
//        ];
//    }

    public function receiptsCreate(Plan $plan, User $user): void
    {
        $params = [
            'amount' => $plan->price,
            'account' => [
                'phone_number' => $user->phone_number
            ],
            'detail' => [
                'receipt_type' => 0,
                'items' => [
                    'title' => "Telegram channel subscription-".$plan->name,
                    'price' => $plan->price,
                    'count' => 1,
                    'code' => "10306013001000000", //get from Soliq
                    'package_code' => "package_code",//also get from Soliq
                    'vat_percent' => 4
                ]
            ]
        ];
        $response = $this->sendRequest('receipts.create', $params);
        if (!$response) {
            return;
        }
        $id = $response['receipt']['_id'];
        Receipt::create([
            'receipt_id' => $id,
        ]);
        $this->receiptsPay($id, $user->cards()->where('verified', true)->latest()->first()->token, $plan);
    }

    public function receiptsPay(string $id, string $token, Plan $plan): void
    {
        $response = $this->sendRequest('receipts.pay', [
            'id' => $id,
            'token' => $token
        ]);
        if ($response['receipt']['state'] == 4) {
            $this->createSubscription($plan, $id);
            $this->receiptsSend($id);
        } else {
            $this->notify('To\'lov amalga oshmadi');
        }
    }

    private function createSubscription(Plan $plan, string $id): void
    {
        $user = User::where('chat_id', $this->chat_id)->first();
        $planTitle = $plan->name;
        $expires = match (true) {
            str_contains($planTitle, 'one-month') => Carbon::now()->addMonth(),
            str_contains($planTitle, 'two-months') => Carbon::now()->addMonths(2),
            str_contains($planTitle, 'six-months') => Carbon::now()->addMonths(6),
            str_contains($planTitle, 'one-year') => Carbon::now()->addYear(),
            default => Carbon::now()->addWeek()
        };

        Subscription::create([
            'user_id' => $user->id,
            'receipt_id' => $id,
            'amount' => $plan->price,
            'expires_at' => $expires,
            'status' => 1,
        ]);
        $this->chat_id = $user->chat_id;
        $this->admin_chat_id = intval(env("ADMIN_CHAT_ID"));
        Telegraph::chat($this->chat_id)
            ->sticker("CAACAgIAAxkBAAExKjRnl0Nr7-7-U-Ita4YDc764z65TRwACiQADFkJrCkbL2losgrCONgQ")->send();

        Telegraph::chat($this->chat_id)
            ->message("🎉 To'g'ri tanlov! \nObuna: ".$expires."gacha\n Rahmat 😇")->send();

        $handler = new HandleChannel($this->getUser($this->chat_id));
        $handler->generateInviteLink();

        Telegraph::chat($this->admin_chat_id)
            ->message("Yangi obuna yaratildi 🎉.\nIsm: ".$user->name." \nTel raqam: ".$user->phone_number."\nObuna: ".$plan->name)
            ->send();
    }

    public function receiptsSend($id): void
    {
        $this->sendRequest('receipts.send', [
            'id' => $id,
            'phone' => substr(User::where('chat_id', $this->chat_id)->first()->phone_number, 1),
        ]);
    }

    private function notify(string|array $message): void
    {
        Telegraph::chat($this->chat_id)->html($message)->send();
    }
}
