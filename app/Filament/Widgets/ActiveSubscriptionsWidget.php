<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ActiveSubscriptionsWidget extends ChartWidget
{
    protected static ?string $heading = 'Active Subscriptions (Last 6 Months)';

    protected function getData(): array
    {
        $months = collect(range(0, 5))->map(function ($i) {
            return now()->subMonths($i)->format('Y-m');
        })->reverse();

        $counts = Subscription::query()
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as month, COUNT(*) as count")
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $labels = $months->toArray();
        $data = $months->map(fn($month) => $counts[$month] ?? 0)->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Subscriptions',
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
