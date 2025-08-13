<?php

namespace App\Telegram\Services\ApiClients;

use App\Telegram\Services\ApiClients\PaymeApiHelpers;

class PaymeCabinetApiClient
{
    use PaymeApiHelpers;

    public function getTransactions(string $businessId, string $from, string $to, string $merchantId, int $page, int $pageSize)
    {
        $skip = ($page - 1) * $pageSize;

        return $this->request('receipts.find', [
            'filter' => [
                'business_id' => $businessId,
                'merchant_id' => [$merchantId],
                'date_from' => $from,
                'date_to' => $to,
                'state' => null
            ],
            'options' => [
                'limit' => $pageSize,
                'skip' => $skip,
                'sort' => -1
            ]
        ]);
    }
}