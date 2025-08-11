<?php

namespace App\Filament\Resources\SubscriptionTransactionResource\Pages;

use App\Filament\Resources\SubscriptionTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSubscriptionTransaction extends ViewRecord
{
    protected static string $resource = SubscriptionTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No edit action for transactions
        ];
    }
}