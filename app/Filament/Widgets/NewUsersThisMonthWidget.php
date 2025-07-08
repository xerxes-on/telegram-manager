<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use Filament\Widgets\Widget;

class NewUsersThisMonthWidget extends Widget
{
    protected static string $view = 'filament.widgets.new-users-this-month-widget';

    public function getData(): array
    {
        $count = Client::query()->whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ])->count();
        return [
            'newUsers' => $count,
        ];
    }
}
