<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use App\Models\Plan;
use Filament\Widgets\ChartWidget;

class SubscriptionDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'Subscription Distribution';

    protected function getData(): array
    {
        $plans = Plan::pluck('name', 'id');
        $counts = Subscription::selectRaw('plan_id, count(*) as total')
            ->groupBy('plan_id')
            ->pluck('total', 'plan_id');

        $labels = [];
        $data = [];
        foreach ($plans as $id => $name) {
            $labels[] = $name;
            $data[] = $counts[$id] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Subscriptions',
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
} 