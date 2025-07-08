<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\Widget;

class ChurnedSubscriptionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.churned-subscriptions-widget';

    public function getData(): array
    {
        $count = Subscription::query()->where('expires_at', '>=', now()->subDays(30))
            ->where('status', false)
            ->count();
        return [
            'churned' => $count,
        ];
    }
}
