<?php

namespace App\Telegram\Services;

use App\Models\Order;
use App\Models\Transaction;
use Carbon\Carbon;

class PaymeService
{

    public function checkPerformTransaction(array $params): array
    {
        // Verify order
        $orderId = $params['account']['order_id'] ?? null;
        $order = Order::query()->find($orderId);

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
        if($order->status !== 'created'){
            return $this->error(-31050, [
                'uz' => 'Buyurtma topilmadi',
                'ru' => 'Заказ не найден',
                'en' => 'Order not found'
            ]);
        }
        // 4. Return success
        return [
            'result' => [
                'allow' => true,
            ],
        ];
    }

    public function createTransaction(array $params): array
    {
        $orderId = $params['account']['order_id'] ?? null;
        $order = Order::query()->find($orderId);

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
        $existing = Transaction::query()->where('order_id', $orderId)
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
                    'transaction' => (string)$transaction->id,
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
                    'transaction' => (string)$first->id,
                    'state' => (int)$first->state,
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
     * Check transaction status.
     */
    public function checkTransaction(array $params): array
    {
        $transaction = Transaction::query()->where('paycom_transaction_id', $params['id'])->first();

        if (!$transaction) {
            return $this->error(-31003, __('telegram.transaction_not_found'));
        }

        // Format response based on transaction state
        return [
            'result' => [
                'create_time' => (int)$transaction->paycom_time,
                'perform_time' => (int)$transaction->perform_time_unix,
                'cancel_time' => (int)$transaction->cancel_time,
                'transaction' => (string)$transaction->id,
                'state' => (int)$transaction->state,
                'reason' => $transaction->reason,
            ],
        ];
    }

    /**
     * Perform a transaction (confirm payment).
     */
    public function performTransaction(array $params): array
    {
        $transaction = Transaction::query()->where('paycom_transaction_id', $params['id'])->first();

        if (!$transaction) {
            return $this->error(-31003, __('telegram.transaction_not_found'));
        }

        // If transaction is in "created" state => move it to "performed"
        if ($transaction->state == 1) {
            $currentMillis = (int)(microtime(true) * 1000);
            $transaction->state = 2;
            $transaction->perform_time = Carbon::now();  // or date('Y-m-d H:i:s')
            $transaction->perform_time_unix = $currentMillis;
            $transaction->update();

            // Mark order as completed
            $order = Order::query()->find($transaction->order_id);
            $order->status = 'charged';
            $order->update();

//            $user = Client::query()->where('phone_number', $order->client->phone_number)->first();
//            if(!empty($user)){
//                $productTitle = $order->plan->name;
//                if (preg_match('/(\d+)-week/', $productTitle, $matches)) {
//                    $weeks = (int) $matches[1];
//                    $expires = Carbon::now()->addWeeks($weeks);
//                } else {
//                    $expires = Carbon::now()->addWeeks();
//                }
//                Subscription::query()->create([
//                    'user_id' => $user->id,
//                    'transaction_id' => $order->id,
//                    'amount' => $order->price,
//                    'expires_at' => $expires,
//                    'status' => 'active',
//                    'payment_method' => 'payme',
//                    'metadata' => json_encode($transaction)
//                ]);
//            }
            return [
                'result' => [
                    'transaction' => (string)$transaction->id,
                    'perform_time' => (int)$transaction->perform_time_unix,
                    'state' => (int)$transaction->state,
                ],
            ];
        }

        // If transaction already performed => return the same info
        if ($transaction->state == 2) {
            return [
                'result' => [
                    'transaction' => (string)$transaction->id,
                    'perform_time' => (int)$transaction->perform_time_unix,
                    'state' => (int)$transaction->state,
                ],
            ];
        }

        // If some other states, you can handle accordingly
        return $this->checkTransaction($params);
    }

    /**
     * Cancel a transaction.
     */
    public function cancelTransaction(array $params): array
    {
        $transaction = Transaction::query()->where('paycom_transaction_id', $params['id'])->first();

        if (!$transaction) {
            return $this->error(-31003, __('telegram.transaction_not_found'));
        }

        $currentMillis = (int)(microtime(true) * 1000);

        // If state == 1 => Cancel and set state = -1
        if ($transaction->state == 1) {
            $transaction->reason = $params['reason'] ?? null;
            $transaction->cancel_time = $currentMillis;
            $transaction->state = -1;
            $transaction->update();

            $order = Order::query()->find($transaction->order_id);
            $order->update(['status' => __('telegram.order_canceled')]);

            return [
                'result' => [
                    'state' => (int)$transaction->state,
                    'cancel_time' => (int)$transaction->cancel_time,
                    'transaction' => (string)$transaction->id,
                ],
            ];
        }

        // If state == 2 => Cancel and set state = -2
        if ($transaction->state == 2) {
            $transaction->reason = $params['reason'] ?? null;
            $transaction->cancel_time = $currentMillis;
            $transaction->state = -2;
            $transaction->update();

            $order = Order::query()->find($transaction->order_id);
            $order->update(['status' => __('telegram.order_canceled')]);

            return [
                'result' => [
                    'state' => (int)$transaction->state,
                    'cancel_time' => (int)$transaction->cancel_time,
                    'transaction' => (string)$transaction->id,
                ],
            ];
        }

        // If already canceled => just return state
        if ($transaction->state == -1 || $transaction->state == -2) {
            return [
                'result' => [
                    'state' => (int)$transaction->state,
                    'cancel_time' => (int)$transaction->cancel_time,
                    'transaction' => (string)$transaction->id,
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
        // Assuming you have a scope or function for time range filtering
        $from = $params['from'] ?? null;
        $to = $params['to'] ?? null;
        $transactions = Transaction::getTransactionsByTimeRange($from, $to);

        return [
            'result' => [
                'transactions' => \App\Http\Resources\TransactionResource::collection($transactions),
            ],
        ];
    }

    /**
     * Change password (not implemented).
     */
    public function changePassword(): array
    {
        return $this->error(-32504, __('telegram.insufficient_privileges'));
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
}
