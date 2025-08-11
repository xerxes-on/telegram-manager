<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionTransactionResource\Pages;
use App\Models\SubscriptionTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionTransactionResource extends Resource
{
    protected static ?string $model = SubscriptionTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('filament.resources.subscription_transaction.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.resources.subscription_transaction.plural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->label(__('filament.fields.client'))
                    ->relationship('client', 'first_name')
                    ->required()
                    ->disabled(),
                Forms\Components\Select::make('subscription_id')
                    ->label(__('filament.fields.subscription'))
                    ->relationship('subscription', 'id')
                    ->disabled(),
                Forms\Components\Select::make('card_id')
                    ->label(__('filament.fields.card'))
                    ->relationship('card', 'masked_number')
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->label(__('filament.fields.amount'))
                    ->numeric()
                    ->prefix('UZS')
                    ->disabled(),
                Forms\Components\TextInput::make('receipt_id')
                    ->label(__('filament.fields.receipt_id'))
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->label(__('filament.fields.status'))
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->disabled(),
                Forms\Components\Select::make('type')
                    ->label(__('filament.fields.type'))
                    ->options([
                        'subscription' => __('filament.transaction_types.subscription'),
                        'renewal' => __('filament.transaction_types.renewal'),
                        'plan_change' => __('filament.transaction_types.plan_change'),
                    ])
                    ->disabled(),
                Forms\Components\Textarea::make('error_message')
                    ->label(__('filament.fields.error_message'))
                    ->columnSpanFull()
                    ->disabled(),
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
                Tables\Columns\TextColumn::make('type')
                    ->label(__('filament.fields.type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'subscription' => 'primary',
                        'renewal' => 'success',
                        'plan_change' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => __('filament.transaction_types.' . $state)),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('filament.fields.amount'))
                    ->money('UZS', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament.fields.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('card.masked_number')
                    ->label(__('filament.fields.card'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('receipt_id')
                    ->label(__('filament.fields.receipt_id'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('filament.filters.type'))
                    ->options([
                        'subscription' => __('filament.transaction_types.subscription'),
                        'renewal' => __('filament.transaction_types.renewal'),
                        'plan_change' => __('filament.transaction_types.plan_change'),
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament.filters.status'))
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for transaction records
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionTransactions::route('/'),
            'view' => Pages\ViewSubscriptionTransaction::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Transactions are created automatically
    }

    public static function canEdit($record): bool
    {
        return false; // Transactions should not be edited
    }

    public static function canDelete($record): bool
    {
        return false; // Transactions should not be deleted
    }
}