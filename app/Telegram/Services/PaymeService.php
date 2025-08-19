<?php

namespace App\Telegram\Services;

use App\Models\Order;
use App\Models\Transaction;
use Carbon\Carbon;

class PaymeService
{
    // PayMe transaction states
    public const STATE_CREATED = 1;
    public const STATE_COMPLETED = 2;
    public const STATE_CANCELLED_BEFORE_PERFORM = -1;
    public const STATE_CANCELLED_AFTER_PERFORM = -2;

    // PayMe error codes
    public const ERROR_INVALID_AMOUNT = -31001;
    public const ERROR_TRANSACTION_NOT_FOUND = -31003;
    public const ERROR_ORDER_NOT_FOUND = -31050;
    public const ERROR_ORDER_UNAVAILABLE = -31051;
    public const ERROR_TRANSACTION_IN_PROGRESS = -31099;
    public const ERROR_INSUFFICIENT_PRIVILEGE = -32504;

    public function checkPerformTransaction(array $params): array
    {
        $orderId = $params['account']['order_id'] ?? null;
        $order = Order::find($orderId);

        if (!$order) {
            return $this->error(self::ERROR_ORDER_NOT_FOUND, [
                'uz' => 'Buyurtma topilmadi',
                'ru' => 'Заказ не найден',
                'en' => 'Order not found'
            ]);
        }

        // 3. Check the amount (PayMe sends amount in cents)
        if (($order->price) != $params['amount']) {
            return $this->error(self::ERROR_INVALID_AMOUNT, [
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
        $order = Order::find($orderId);

        // 2. Validate order
        if (!$order) {
            return $this->error(self::ERROR_ORDER_NOT_FOUND, [
                'uz' => 'Buyurtma topilmadi',
                'ru' => 'Заказ не найден',
                'en' => 'Order not found'
            ]);
        }

        // 3. Validate amount (PayMe sends amount in cents)
        if (($order->price) != $params['amount']) {
            return $this->error(self::ERROR_INVALID_AMOUNT, [
                'uz' => 'Notogri summa',
                'ru' => 'Неверная сумма',
                'en' => 'Incorrect amount'
            ]);
        }

        // 4. Check existing transaction for the same order and same Paycom ID/time
        $existing = Transaction::where('order_id', $orderId)
            ->whereIn('state', [self::STATE_CREATED, self::STATE_COMPLETED])
            ->get();

        //   (a) If no transaction yet, create new
        if ($existing->count() === 0) {
            $transaction = Transaction::create([
                "paycom_transaction_id" => $params['id'],
                "paycom_time" => (string) $params['time'],
                "paycom_time_datetime" => now(),
                "amount" => $params['amount'],
                "state" => self::STATE_CREATED,
                "order_id" => $orderId
            ]);

            return [
                'result' => [
                    'create_time' => (int) $transaction->paycom_time,
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
        return $this->error(self::ERROR_TRANSACTION_IN_PROGRESS, [
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
        $transaction = Transaction::where('paycom_transaction_id', $params['id'])->first();

        if (!$transaction) {
            return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found.');
        }

        // Format response based on transaction state
        return [
            'result' => [
                'create_time' => $this->safeIntCast($transaction->paycom_time),
                'perform_time' => $this->safeIntCast($transaction->perform_time_unix ?? 0),
                'cancel_time' => $this->safeIntCast($transaction->cancel_time ?? 0),
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
        $transaction = Transaction::where('paycom_transaction_id', $params['id'])->first();

        if (!$transaction) {
            return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Транзакция не найдена');
        }

        // If transaction is in "created" state => move it to "performed"
        if ($transaction->state == self::STATE_CREATED) {
            $currentMillis = (int) (microtime(true) * 1000);
            $transaction->state = self::STATE_COMPLETED;
            $transaction->perform_time = Carbon::now();  // or date('Y-m-d H:i:s')
            $transaction->perform_time_unix = (string) $currentMillis;
            $transaction->update();

            // Mark order as completed
            $order = Order::find($transaction->order_id);
            $order->status = 'charged';
            $order->update();

            return [
                'result' => [
                    'transaction' => (string) $transaction->id,
                    'perform_time' => (int) $transaction->perform_time_unix,
                    'state' => (int) $transaction->state,
                ],
            ];
        }

        // If transaction already performed => return the same info
        if ($transaction->state == self::STATE_COMPLETED) {
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
     * Cancel a transaction.
     */
    public function cancelTransaction(array $params): array
    {
        $transaction = Transaction::where('paycom_transaction_id', $params['id'])->first();

        if (!$transaction) {
            return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found');
        }

        $currentMillis = (int) (microtime(true) * 1000);

        // If state == 1 => Cancel and set state = -1
        if ($transaction->state == self::STATE_CREATED) {
            $transaction->reason = $params['reason'] ?? null;
            $transaction->cancel_time = (string) $currentMillis;
            $transaction->state = self::STATE_CANCELLED_BEFORE_PERFORM;
            $transaction->update();

            $order = Order::find($transaction->order_id);
            $order->update(['status' => 'cancelled']);

            return [
                'result' => [
                    'state' => (int) $transaction->state,
                    'cancel_time' => (int) $transaction->cancel_time,
                    'transaction' => (string) $transaction->id,
                ],
            ];
        }

        // If state == 2 => Cancel and set state = -2
        if ($transaction->state == self::STATE_COMPLETED) {
            $transaction->reason = $params['reason'] ?? null;
            $transaction->cancel_time = (string) $currentMillis;
            $transaction->state = self::STATE_CANCELLED_AFTER_PERFORM;
            $transaction->update();

            $order = Order::find($transaction->order_id);
            $order->update(['status' => 'cancelled']);

            return [
                'result' => [
                    'state' => (int) $transaction->state,
                    'cancel_time' => (int) $transaction->cancel_time,
                    'transaction' => (string) $transaction->id,
                ],
            ];
        }

        // If already canceled => just return state
        if ($transaction->state == self::STATE_CANCELLED_BEFORE_PERFORM || $transaction->state == self::STATE_CANCELLED_AFTER_PERFORM) {
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

        if (!$from || !$to) {
            return $this->error(self::ERROR_INVALID_AMOUNT, 'Invalid time range parameters');
        }

        $transactions = Transaction::whereBetween('paycom_time', [(string)$from, (string)$to])->get();

        $formattedTransactions = $transactions->map(function ($transaction) {
            return [
                'id' => (string) $transaction->id,
                'time' => $this->safeIntCast($transaction->paycom_time),
                'amount' => (int) $transaction->amount,
                'account' => [
                    'order_id' => (string) $transaction->order_id
                ],
                'create_time' => $this->safeIntCast($transaction->paycom_time),
                'perform_time' => $this->safeIntCast($transaction->perform_time_unix ?? 0),
                'cancel_time' => $this->safeIntCast($transaction->cancel_time ?? 0),
                'transaction' => (string) $transaction->paycom_transaction_id,
                'state' => (int) $transaction->state,
                'reason' => $transaction->reason,
            ];
        });

        return [
            'result' => [
                'transactions' => $formattedTransactions->toArray(),
            ],
        ];
    }

    /**
     * Change password (not implemented).
     */
    public function changePassword(): array
    {
        return $this->error(self::ERROR_INSUFFICIENT_PRIVILEGE, 'Недостаточно привилегий для выполнения метода');
    }

    /**
     * Safely cast a value to integer, handling large numbers correctly.
     * This prevents 32-bit integer overflow issues with PayMe timestamps.
     */
    private function safeIntCast($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        // Handle string values that might represent large integers
        $stringValue = trim((string) $value);

        if (!is_numeric($stringValue)) {
            return 0;
        }

        // If the value looks like it was corrupted by 32-bit overflow, try to detect and fix
        $numValue = floatval($stringValue);

        // If we have a negative number that could be an overflow, check if it makes sense
        if ($numValue < 0 && $numValue > -2147483648) {
            // This might be a 32-bit overflow - try to recover the original value
            $recovered = $numValue + 4294967296; // Add 2^32

            // Check if the recovered value makes sense as a PayMe timestamp (should be around current time in ms)
            $currentTimeMs = time() * 1000;
            $oneYearInMs = 365 * 24 * 60 * 60 * 1000;

            if ($recovered > ($currentTimeMs - $oneYearInMs) && $recovered < ($currentTimeMs + $oneYearInMs)) {
                return (int) $recovered;
            }
        }

        // For very large numbers, ensure they fit in 64-bit int
        if ($numValue > PHP_INT_MAX) {
            return PHP_INT_MAX;
        }

        if ($numValue < PHP_INT_MIN) {
            return PHP_INT_MIN;
        }

        return (int) $numValue;
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
