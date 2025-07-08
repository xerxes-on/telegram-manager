<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChurnedSubscriptionsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $count = \App\Models\Subscription::query()->where('expires_at', '>=', now()->subDays(30))
            ->where('status', false)
            ->count();
        return [
            Stat::make('Churned Subscriptions (30d)', $count)
                ->color('danger'),
        ];
    }
}
