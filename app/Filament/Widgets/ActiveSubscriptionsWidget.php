<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\Widget;

class ActiveSubscriptionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.active-subscriptions-widget';

    public function getData(): array
    {
        return [
            'activeSubscriptions' => Subscription::query()->where('status', true)->count(),
        ];
    }
}
