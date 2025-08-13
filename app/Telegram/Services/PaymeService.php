<?php

namespace App\Telegram\Services;

use App\Models\Order;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymeService
{

    public function checkPerformTransaction(array $params): array
    {
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

    public function createTransaction(array $params): array
    {
        $orderId = $params['account']['order_id'] ?? null;

        // Idempotency by Payme transaction id: if we already saw this id, return it
        $existingByPaymeId = Transaction::where('paycom_transaction_id', $params['id'])->first();
        if ($existingByPaymeId) {
            return [
                'result' => [
                    'create_time' => (int) $existingByPaymeId->paycom_time ?: (int) $params['time'],
                    'transaction' => (string) $existingByPaymeId->id,
                    'state' => (int) $existingByPaymeId->state,
                ],
            ];
        }

        return DB::transaction(function () use ($orderId, $params) {
            // Lock the order row to serialize concurrent creates for the same order
            $order = Order::whereKey($orderId)->lockForUpdate()->first();

            // Validate order
            if (!$order) {
                return $this->error(-31050, [
                    'uz' => 'Buyurtma topilmadi',
                    'ru' => 'Заказ не найден',
                    'en' => 'Order not found'
                ]);
            }

            // Validate amount
            if ($order->price != $params['amount']) {
                return $this->error(-31001, [
                    'uz' => 'Notogri summa',
                    'ru' => 'Неверная сумма',
                    'en' => 'Incorrect amount'
                ]);
            }

            // Check if there is already a pending transaction for this order
            $pending = Transaction::where('order_id', $orderId)
                ->where('state', 1)
                ->first();

            // If the same payme id/time was already saved for this order, return it
            if ($pending
                && $pending->paycom_transaction_id == $params['id']
                && $pending->paycom_time == $params['time']) {
                return [
                    'result' => [
                        'create_time' => (int) $pending->paycom_time,
                        'transaction' => (string) $pending->id,
                        'state' => (int) $pending->state,
                    ],
                ];
            }

            // If some OTHER pending transaction exists for this order, tell Payme it's processing
            if ($pending) {
                return $this->error(-31099, [
                    'uz' => 'Buyurtma tolovi hozirda amalga oshrilmoqda',
                    'ru' => 'Оплата заказа в данный момент обрабатывается',
                    'en' => 'Order payment is currently being processed'
                ]);
            }

            // Otherwise, create a new pending transaction
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
                    'create_time' => (int) $transaction->paycom_time,
                    'transaction' => (string) $transaction->id,
                    'state' => (int) $transaction->state,
                ],
            ];
        }, 3);
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
     * Perform a transaction (confirm payment).
     */
    public function performTransaction(array $params): array
    {
        return DB::transaction(function () use ($params) {
            // Lock the transaction row to avoid double-perform
            $transaction = Transaction::where('paycom_transaction_id', $params['id'])
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                return $this->error(-31003, 'Транзакция не найдена');
            }

            // If transaction is in "created" state => move it to "performed"
            if ($transaction->state == 1) {
                $currentMillis = (int) (microtime(true) * 1000);
                $transaction->state = 2;
                $transaction->perform_time = Carbon::now();
                $transaction->perform_time_unix = $currentMillis;
                $transaction->update();

                // Mark order as completed
                $order = Order::find($transaction->order_id);
                $order->status = 'charged';
                $order->update();

                $user = User::where('phone_number', $order->client->phone_number)->first();
                if (!empty($user)) {
                    $productTitle = $order->product->title;
                    if (preg_match('/(\d+)-week/', $productTitle, $matches)) {
                        $weeks = (int) $matches[1];
                        $expires = Carbon::now()->addWeeks($weeks);
                    } else {
                        $expires = Carbon::now()->addWeeks();
                    }
                    Subscription::create([
                        'user_id' => $user->id,
                        'transaction_id' => $order->id,
                        'amount' => $order->price,
                        'expires_at' => $expires,
                        'status' => 'active',
                        'payment_method' => 'payme',
                        'metadata' => json_encode($transaction),
                        'product_id' => $order->product->id
                    ]);
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
        }, 3);
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
        return $this->error(-32504, 'Недостаточно привилегий для выполнения метода');
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
