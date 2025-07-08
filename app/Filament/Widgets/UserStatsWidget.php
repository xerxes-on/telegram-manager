<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use Filament\Widgets\Widget;

class UserStatsWidget extends Widget
{
    protected static string $view = 'filament.widgets.user-stats-widget';

    public function getData(): array
    {
        return [
            'totalUsers' => Client::query()->count(),
        ];
    }
}
