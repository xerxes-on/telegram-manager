<?php

namespace App\Telegram\Services;

use App\Telegram\Services\ApiClients\PaymeCabinetApiClient;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PaymeTransactionFetchService
{
    protected PaymeCabinetApiClient $client;
    
    public function __construct()
    {
        $config = config('services.paycom.cabinet');
        
        $this->client = new PaymeCabinetApiClient(
            login: $config['login'],
            password: $config['password'],
            deviceId: $config['device_id'],
            deviceKey: $config['device_key']
        );
    }
    
    /**
     * Fetch transactions for a specific date range
     * 
     * @param Carbon $from
     * @param Carbon $to
     * @param int $pageSize
     * @return Collection
     */
    public function fetchTransactions(Carbon $from, Carbon $to, int $pageSize = 1000): Collection
    {
        $transactions = collect();
        $page = 1;
        
        try {
            // PayMe API expects dates in specific format with timezone
            $fromFormatted = $from->clone()->subDay()->format('Y-m-d\TH:i:s.000\Z');
            $toFormatted = $to->clone()->addDay()->format('Y-m-d\TH:i:s.000\Z');
            
            do {
                $response = $this->client->getTransactions(
                    businessId: config('services.paycom.cabinet.business_id'),
                    from: $fromFormatted,
                    to: $toFormatted,
                    merchantId: config('services.paycom.cabinet.merchant_id'),
                    page: $page,
                    pageSize: $pageSize
                );
                
                $data = $response->json();
                
                if (isset($data['error'])) {
                    Log::error('PayMe API error', [
                        'error' => $data['error'],
                        'from' => $fromFormatted,
                        'to' => $toFormatted,
                        'page' => $page
                    ]);
                    break;
                }
                
                $results = data_get($data, 'result', []);
                
                if (empty($results)) {
                    break;
                }
                
                $transactions = $transactions->concat($results);
                
                // Check if there are more pages
                if (count($results) < $pageSize) {
                    break;
                }
                
                $page++;
                
            } while (true);
            
        } catch (\Exception $e) {
            Log::error('Error fetching PayMe transactions', [
                'error' => $e->getMessage(),
                'from' => $from->toDateTimeString(),
                'to' => $to->toDateTimeString()
            ]);
        }
        
        return $transactions;
    }
    
    /**
     * Find a specific transaction by its PayMe ID
     * 
     * @param string $paymeTransactionId
     * @param Carbon|null $approximateDate
     * @return array|null
     */
    public function findTransaction(string $paymeTransactionId, ?Carbon $approximateDate = null): ?array
    {
        // If no date provided, search last 30 days
        if (!$approximateDate) {
            $approximateDate = now();
        }
        
        // Search in a 7-day window around the approximate date
        $from = $approximateDate->copy()->subDays(3);
        $to = $approximateDate->copy()->addDays(3);
        
        $transactions = $this->fetchTransactions($from, $to);
        
        return $transactions->firstWhere('_id', $paymeTransactionId);
    }
    
    /**
     * Get transaction status from PayMe
     * 
     * @param string $paymeTransactionId
     * @return int|null
     */
    public function getTransactionStatus(string $paymeTransactionId): ?int
    {
        $transaction = $this->findTransaction($paymeTransactionId);
        
        if (!$transaction) {
            return null;
        }
        
        return data_get($transaction, 'state');
    }
}