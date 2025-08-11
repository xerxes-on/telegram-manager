<?php

namespace App\Filament\Resources\SubscriptionTransactionResource\Pages;

use App\Filament\Resources\SubscriptionTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionTransactions extends ListRecords
{
    protected static string $resource = SubscriptionTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for transactions
        ];
    }
}