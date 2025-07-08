<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class NewUsersThisMonthWidget extends Widget
{
    protected static string $view = 'filament.widgets.new-users-this-month-widget';

    public function getData(): array
    {
        $count = User::whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ])->count();
        return [
            'newUsers' => $count,
        ];
    }
} 