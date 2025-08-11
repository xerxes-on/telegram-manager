<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class TransactionStats extends BaseWidget
{
    protected function getStats(): array
    {
        $todayTransactions = Transaction::query()
            ->whereDate('paycom_time_datetime', today())
            ->where('state', 2)
            ->sum('amount');

        $monthTransactions = Transaction::query()
            ->whereMonth('paycom_time_datetime', now()->month)
            ->whereYear('paycom_time_datetime', now()->year)
            ->where('state', 2)
            ->sum('amount');

        $totalTransactions = Transaction::query()
            ->where('state', 2)
            ->sum('amount');

        return [
            Stat::make('Today\'s Revenue', number_format($todayTransactions / 100, 0, '.', ' ') . ' UZS')
                ->description('Completed transactions today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('This Month', number_format($monthTransactions / 100, 0, '.', ' ') . ' UZS')
                ->description('Total for ' . now()->format('F'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
            Stat::make('All Time', number_format($totalTransactions / 100, 0, '.', ' ') . ' UZS')
                ->description('Total revenue')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }
}