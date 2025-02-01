<?php

namespace App\Telegram\Services;

use AllowDynamicProperties;
use App\Http\Resources\TransactionResource;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Telegram\Traits\CanAlterUsers;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;


#[AllowDynamicProperties] class PaymeService extends WebhookHandler
{
    use CanAlterUsers;

    public function checkPerformTransaction(array $params): array
    {
        // Verify order
        $orderId = $params['account']['order_id'] ?? null;
        $order = Order::find($orderId);

        if (!$order) {
            return $this->error(-31050, [
                'uz' => 'Buyurtma topilmadi',
                'ru' => 'Заказ не найден',
                'en' => 'Order not found'
            ]);
        }

        // 3. Check the amount
        if ($order->price != $params['amount']) {
            return $this->error(-31001, [
                'uz' => 'Notogri summa',
                'ru' => 'Неверная сумма',
                'en' => 'Incorrect amount'
            ]);
        }

        // 4. Return success
        return [
            'result' => [
                'allow' => true,
            ],
        ];
    }

    /**
     * Helper to format error responses consistently.
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

    public function createTransaction(array $params): array
    {
        $orderId = $params['account']['order_id'] ?? null;
        $order = Order::find($orderId);

        // 2. Validate order
        if (!$order) {
            return $this->error(-31050, [
                'uz' => 'Buyurtma topilmadi',
                'ru' => 'Заказ не найден',
                'en' => 'Order not found'
            ]);
        }

        // 3. Validate amount
        if ($order->price != $params['amount']) {
            return $this->error(-31001, [
                'uz' => 'Notogri summa',
                'ru' => 'Неверная сумма',
                'en' => 'Incorrect amount'
            ]);
        }

        // 4. Check existing transaction for the same order and same Paycom ID/time
        $existing = Transaction::where('order_id', $orderId)
            ->where('state', 1)
            ->get();

        //   (a) If no transaction yet, create new
        if ($existing->count() === 0) {
            $transaction = new Transaction();
            $transaction->paycom_transaction_id = $params['id'];
            $transaction->paycom_time = $params['time'];
            $transaction->paycom_time_datetime = now();
            $transaction->amount = $params['amount'];
            $transaction->state = 1;
            $transaction->order_id = $orderId;
            $transaction->save();

            return [
                'result' => [
                    'create_time' => $params['time'],
                    'transaction' => (string) $transaction->id,
                    'state' => $transaction->state,
                ],
            ];
        }

        //   (b) If transaction with the same time & paycom_transaction_id exists
        $first = $existing->first();
        if (
            $existing->count() === 1
            && $first->paycom_time == $params['time']
            && $first->paycom_transaction_id == $params['id']
        ) {
            return [
                'result' => [
                    'create_time' => $params['time'],
                    'transaction' => (string) $first->id,
                    'state' => (int) $first->state,
                ],
            ];
        }

        //   (c) Otherwise, transaction for this order is being processed
        return $this->error(-31099, [
            'uz' => 'Buyurtma tolovi hozirda amalga oshrilmoqda',
            'ru' => 'Оплата заказа в данный момент обрабатывается',
            'en' => 'Order payment is currently being processed'
        ]);
    }

    /**
     * Perform a transaction (confirm payment).
     */
    public function performTransaction(array $params): array
    {
        $transaction = Transaction::where('paycom_transaction_id', $params['id'])->first();

        if (!$transaction) {
            return $this->error(-31003, 'Транзакция не найдена');
        }

        if ($transaction->state == 1) {
            $currentMillis = (int) (microtime(true) * 1000);
            $transaction->state = 2;
            $transaction->perform_time = Carbon::now();  // or date('Y-m-d H:i:s')
            $transaction->perform_time_unix = $currentMillis;
            $transaction->update();

            // Mark order as completed
            $order = Order::find($transaction->order_id);
            $order->status = 'charged';
            $order->update();

            $user = User::find($order->user_id);
            if (!empty($user)) {
                $this->chat_id = $user->chat_id;
                $this->admin_chat_id = intval(env("ADMIN_CHAT_ID"));
                $planTitle = $order->plan->name;
                $expires = match (true) {
                    str_contains($planTitle, 'one-month') => Carbon::now()->addMonth(),
                    str_contains($planTitle, 'two-months') => Carbon::now()->addMonths(2),
                    str_contains($planTitle, 'six-months') => Carbon::now()->addMonths(6),
                    str_contains($planTitle, 'one-year') => Carbon::now()->addYear(),
                    default => Carbon::now()->addWeek()
                };
                Subscription::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'amount' => $order->price,
                    'expires_at' => $expires,
                    'status' => 1,
                ]);
                $sticker = "CAACAgIAAxkBAAExKjRnl0Nr7-7-U-Ita4YDc764z65TRwACiQADFkJrCkbL2losgrCONgQ";
                Telegraph::chat($this->chat_id)
                    ->sticker($sticker)->send();

                Telegraph::chat($this->chat_id)
                    ->message("🎉 Mavaffaqiyatga yana bir qadam! \nMuddati: ".$expires."\n Foydali qadam 😇")->send();

                $handler = new HandleChannel($this->getUser($this->chat_id));
                $handler->generateInviteLink();

                Telegraph::chat($this->admin_chat_id)
                    ->message("Yangi obuna yaratildi 🎉.\nIsm: ".$user->name." \nTel raqam: ".$user->phone_number."\nObuna: ".$order->plan->name)
                    ->send();
            }
            return [
                'result' => [
                    'transaction' => (string) $transaction->id,
                    'perform_time' => (int) $transaction->perform_time_unix,
                    'state' => (int) $transaction->state,
                ],
            ];
        }

        // If transaction already performed => return the same info
        if ($transaction->state == 2) {
            return [
                'result' => [
                    'transaction' => (string) $transaction->id,
                    'perform_time' => (int) $transaction->perform_time_unix,
                    'state' => (int) $transaction->state,
                ],
            ];
        }

        // If some other states, you can handle accordingly
        return $this->checkTransaction($params);
    }

    /**
     * Check transaction status.
     */
    public function checkTransaction(array $params): array
    {
        $transaction = Transaction::where('paycom_transaction_id', $params['id'])->first();

        if (!$transaction) {
            return $this->error(-31003, 'Transaction not found.');
        }

        // Format response based on transaction state
        return [
            'result' => [
                'create_time' => (int) $transaction->paycom_time,
                'perform_time' => (int) $transaction->perform_time_unix,
                'cancel_time' => (int) $transaction->cancel_time,
                'transaction' => (string) $transaction->id,
                'state' => (int) $transaction->state,
                'reason' => $transaction->reason,
            ],
        ];
    }

    /**
     * Cancel a transaction.
     */
    public function cancelTransaction(array $params): array
    {
        $transaction = Transaction::where('paycom_transaction_id', $params['id'])->first();

        if (!$transaction) {
            return $this->error(-31003, 'Transaction not found');
        }

        $currentMillis = (int) (microtime(true) * 1000);

        // If state == 1 => Cancel and set state = -1
        if ($transaction->state == 1) {
            $transaction->reason = $params['reason'] ?? null;
            $transaction->cancel_time = $currentMillis;
            $transaction->state = -1;
            $transaction->update();

            $order = Order::find($transaction->order_id);
            $order->update(['status' => 'canceled']);

            return [
                'result' => [
                    'state' => (int) $transaction->state,
                    'cancel_time' => (int) $transaction->cancel_time,
                    'transaction' => (string) $transaction->id,
                ],
            ];
        }

        // If state == 2 => Cancel and set state = -2
        if ($transaction->state == 2) {
            $transaction->reason = $params['reason'] ?? null;
            $transaction->cancel_time = $currentMillis;
            $transaction->state = -2;
            $transaction->update();

            $order = Order::find($transaction->order_id);
            $order->update(['status' => 'bekor qilindi']);

            return [
                'result' => [
                    'state' => (int) $transaction->state,
                    'cancel_time' => (int) $transaction->cancel_time,
                    'transaction' => (string) $transaction->id,
                ],
            ];
        }

        // If already canceled => just return state
        if ($transaction->state == -1 || $transaction->state == -2) {
            return [
                'result' => [
                    'state' => (int) $transaction->state,
                    'cancel_time' => (int) $transaction->cancel_time,
                    'transaction' => (string) $transaction->id,
                ],
            ];
        }

        // Otherwise, handle how you wish
        return $this->checkTransaction($params);
    }

    /**
     * Get statement of transactions.
     */
    public function getStatement(array $params): array
    {
        $from = $params['from'] ?? null;
        $to = $params['to'] ?? null;
        $transactions = Transaction::getTransactionsByTimeRange($from, $to);

        return [
            'result' => [
                'transactions' => TransactionResource::collection($transactions),
            ],
        ];
    }

    /**
     * Change password (not implemented).
     */
    public function changePassword(): array
    {
        return $this->error(-32504, 'Недостаточно привилегий для выполнения метода');
    }
}
