<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;

class UserStatsWidget extends Widget
{
    protected static string $view = 'filament.widgets.user-stats-widget';

    public function getData(): array
    {
        return [
            'totalUsers' => User::count(),
        ];
    }
} 