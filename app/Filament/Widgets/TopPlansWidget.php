<?php

namespace App\Filament\Widgets;

use App\Models\Plan;
use App\Models\Subscription;
use Filament\Widgets\ChartWidget;

class TopPlansWidget extends ChartWidget
{
    protected static ?string $heading = 'Top Plans';

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
        return 'pie';
    }
} 