<?php

namespace App\Telegram\Services;

use App\Models\Order;
use App\Models\Transaction;
use App\Telegram\Services\PaymeTransactionFetchService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Enhanced PayMe service that can verify transactions using PayMe Cabinet API
 * This extends the basic webhook functionality with additional verification capabilities
 */
class PaymeEnhancedService extends PaymeService
{
    protected PaymeTransactionFetchService $fetchService;
    
    public function __construct()
    {
        $this->fetchService = new PaymeTransactionFetchService();
    }
    
    /**
     * Verify a transaction against PayMe's records
     * Useful for reconciliation and dispute resolution
     * 
     * @param Transaction $transaction
     * @return array
     */
    public function verifyTransaction(Transaction $transaction): array
    {
        try {
            // Fetch the transaction from PayMe's API
            $paymeTransaction = $this->fetchService->findTransaction(
                $transaction->paycom_transaction_id,
                Carbon::parse($transaction->paycom_time_datetime)
            );
            
            if (!$paymeTransaction) {
                return [
                    'verified' => false,
                    'status' => 'not_found',
                    'message' => 'Transaction not found in PayMe records'
                ];
            }
            
            $paymeState = data_get($paymeTransaction, 'state');
            $paymeAmount = data_get($paymeTransaction, 'amount');
            
            // Verify transaction details
            $discrepancies = [];
            
            if ($transaction->state != $paymeState) {
                $discrepancies[] = "State mismatch: Local={$transaction->state}, PayMe={$paymeState}";
            }
            
            if ($transaction->amount != $paymeAmount) {
                $discrepancies[] = "Amount mismatch: Local={$transaction->amount}, PayMe={$paymeAmount}";
            }
            
            if (empty($discrepancies)) {
                return [
                    'verified' => true,
                    'status' => 'match',
                    'message' => 'Transaction verified successfully',
                    'payme_data' => $paymeTransaction
                ];
            } else {
                return [
                    'verified' => false,
                    'status' => 'mismatch',
                    'message' => 'Transaction discrepancies found',
                    'discrepancies' => $discrepancies,
                    'payme_data' => $paymeTransaction
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Error verifying transaction', [
                'transaction_id' => $transaction->id,
                'paycom_transaction_id' => $transaction->paycom_transaction_id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'verified' => false,
                'status' => 'error',
                'message' => 'Error occurred during verification: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enhanced transaction status check that uses PayMe API for verification
     * 
     * @param array $params
     * @return array
     */
    public function checkTransactionEnhanced(array $params): array
    {
        // First check local database
        $result = $this->checkTransaction($params);
        
        // If verification is enabled and transaction exists locally
        if (config('services.paycom.verify_transactions', false) && 
            !isset($result['error'])) {
            
            $transaction = Transaction::where('paycom_transaction_id', $params['id'])->first();
            
            if ($transaction) {
                $verification = $this->verifyTransaction($transaction);
                
                if (!$verification['verified']) {
                    Log::warning('Transaction verification failed', [
                        'transaction_id' => $transaction->id,
                        'verification_result' => $verification
                    ]);
                    
                    // Optionally add verification info to response
                    $result['verification'] = $verification;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Reconcile transactions for a specific date range
     * Compares local transactions with PayMe records
     * 
     * @param Carbon $from
     * @param Carbon $to
     * @return array
     */
    public function reconcileTransactions(Carbon $from, Carbon $to): array
    {
        $report = [
            'period' => [
                'from' => $from->toDateTimeString(),
                'to' => $to->toDateTimeString()
            ],
            'summary' => [
                'local_transactions' => 0,
                'payme_transactions' => 0,
                'matched' => 0,
                'discrepancies' => 0,
                'local_only' => 0,
                'payme_only' => 0
            ],
            'discrepancies' => [],
            'local_only' => [],
            'payme_only' => []
        ];
        
        try {
            // Get local transactions
            $localTransactions = Transaction::whereBetween('paycom_time_datetime', [
                $from->toDateTimeString(),
                $to->toDateTimeString()
            ])->get()->keyBy('paycom_transaction_id');
            
            // Get PayMe transactions
            $paymeTransactions = $this->fetchService->fetchTransactions($from, $to)
                ->keyBy('_id');
            
            $report['summary']['local_transactions'] = $localTransactions->count();
            $report['summary']['payme_transactions'] = $paymeTransactions->count();
            
            // Find matched transactions and check for discrepancies
            foreach ($localTransactions as $transactionId => $localTransaction) {
                if (isset($paymeTransactions[$transactionId])) {
                    $paymeTransaction = $paymeTransactions[$transactionId];
                    $verification = $this->verifyTransaction($localTransaction);
                    
                    if ($verification['verified']) {
                        $report['summary']['matched']++;
                    } else {
                        $report['summary']['discrepancies']++;
                        $report['discrepancies'][] = [
                            'transaction_id' => $transactionId,
                            'local_data' => $localTransaction->toArray(),
                            'verification' => $verification
                        ];
                    }
                } else {
                    $report['summary']['local_only']++;
                    $report['local_only'][] = $localTransaction->toArray();
                }
            }
            
            // Find PayMe-only transactions
            foreach ($paymeTransactions as $transactionId => $paymeTransaction) {
                if (!isset($localTransactions[$transactionId])) {
                    $report['summary']['payme_only']++;
                    $report['payme_only'][] = $paymeTransaction;
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error during transaction reconciliation', [
                'error' => $e->getMessage(),
                'period' => $report['period']
            ]);
            
            $report['error'] = $e->getMessage();
        }
        
        return $report;
    }
}