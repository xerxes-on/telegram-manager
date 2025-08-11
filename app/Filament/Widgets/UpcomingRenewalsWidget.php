<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingRenewalsWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Subscription::query()
                    ->with(['client', 'plan'])
                    ->where('status', true)
                    ->whereBetween('expires_at', [now(), now()->addDays(7)])
                    ->orderBy('expires_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('client.first_name')
                    ->label(__('filament.fields.client_name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.phone_number')
                    ->label(__('filament.fields.phone_number')),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label(__('filament.fields.plan')),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('filament.fields.expires_at'))
                    ->date()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('daysUntilExpiry')
                    ->label(__('filament.fields.days_until_expiry'))
                    ->state(fn ($record) => $record->daysUntilExpiry())
                    ->suffix(' ' . __('days'))
                    ->color(fn ($state) => $state <= 3 ? 'danger' : 'warning'),
                Tables\Columns\IconColumn::make('canRenewEarly')
                    ->label('Can Renew')
                    ->state(fn ($record) => $record->canRenewEarly())
                    ->boolean(),
                Tables\Columns\TextColumn::make('reminder_count')
                    ->label(__('filament.fields.reminder_count'))
                    ->numeric()
                    ->badge()
                    ->color(fn ($state) => $state >= 3 ? 'success' : 'warning'),
            ])
            ->paginated([10, 25, 50])
            ->heading(__('filament.widgets.upcoming_renewals'));
    }
}