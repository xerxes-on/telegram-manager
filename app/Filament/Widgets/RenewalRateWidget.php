<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RenewalRateWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $totalRenewals = Subscription::where('is_renewal', true)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->count();
            
        $successfulRenewals = Subscription::where('is_renewal', true)
            ->where('status', true)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->count();
            
        $expiringThisWeek = Subscription::where('status', true)
            ->whereBetween('expires_at', [now(), now()->addDays(7)])
            ->count();
            
        $expiringToday = Subscription::where('status', true)
            ->whereDate('expires_at', now()->toDateString())
            ->count();
            
        $renewalRate = $totalRenewals > 0 
            ? round(($successfulRenewals / $totalRenewals) * 100, 1) 
            : 0;

        return [
            Stat::make(__('filament.widgets.renewal_rate'), $renewalRate . '%')
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($renewalRate > 70 ? 'success' : 'warning')
                ->chart($this->getRenewalTrend()),
                
            Stat::make('Expiring This Week', $expiringThisWeek)
                ->description($expiringToday . ' expiring today')
                ->descriptionIcon('heroicon-m-clock')
                ->color($expiringToday > 0 ? 'danger' : 'warning'),
                
            Stat::make('Total Renewals', $successfulRenewals)
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
    
    protected function getRenewalTrend(): array
    {
        // Get renewal counts for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Subscription::where('is_renewal', true)
                ->whereDate('created_at', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }
}