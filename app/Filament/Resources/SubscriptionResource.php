<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Filament\Resources\SubscriptionResource\RelationManagers;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'first_name')
                    ->required(),
                Forms\Components\Toggle::make('status')
                    ->required(),
                Forms\Components\TextInput::make('order_id')
                    ->required()
                    ->numeric(),
                Forms\Components\DatePicker::make('expires_at')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.first_name')
                    ->label(__('filament.fields.client_name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.phone_number')
                    ->label(__('filament.fields.phone_number'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label(__('filament.fields.plan'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.price')
                    ->label(__('filament.fields.price'))
                    ->money('UZS', divideBy: 100)
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->boolean()
                    ->label(__('filament.fields.active')),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('filament.fields.expires_at'))
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state->isPast() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('daysUntilExpiry')
                    ->label(__('filament.fields.days_until_expiry'))
                    ->state(fn ($record) => $record->daysUntilExpiry())
                    ->suffix(' ' . __('days'))
                    ->color(fn ($state) => $state <= 3 ? 'warning' : 'success')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_renewal')
                    ->label(__('filament.fields.is_renewal'))
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('payment_retry_count')
                    ->label(__('filament.fields.payment_retry_count'))
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reminder_count')
                    ->label(__('filament.fields.reminder_count'))
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_payment_attempt')
                    ->label(__('filament.fields.last_payment_attempt'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status')
                    ->label(__('filament.filters.active_status')),
                Tables\Filters\Filter::make('expired')
                    ->label(__('filament.filters.expired'))
                    ->query(fn ($query) => $query->where('expires_at', '<', now())),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label(__('filament.filters.expiring_soon'))
                    ->query(fn ($query) => $query->where('expires_at', '<=', now()->addDays(3))
                        ->where('expires_at', '>', now())),
                Tables\Filters\SelectFilter::make('plan')
                    ->label(__('filament.filters.plan'))
                    ->relationship('plan', 'name'),
                Tables\Filters\TernaryFilter::make('is_renewal')
                    ->label(__('filament.filters.is_renewal')),
                Tables\Filters\Filter::make('has_reminder')
                    ->label(__('filament.filters.has_reminder'))
                    ->query(fn ($query) => $query->where('reminder_count', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'view' => Pages\ViewSubscription::route('/{record}'),
        ];
    }
}
