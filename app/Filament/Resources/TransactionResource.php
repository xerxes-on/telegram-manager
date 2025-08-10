<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('paycom_transaction_id')
                    ->label('Paycom Transaction ID')
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->disabled(),
                Forms\Components\Select::make('state')
                    ->options([
                        -2 => 'Cancelled after perform',
                        -1 => 'Cancelled',
                        1 => 'Created',
                        2 => 'Performed',
                    ])
                    ->disabled(),
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'id')
                    ->disabled(),
                Forms\Components\TextInput::make('reason')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('paycom_time_datetime')
                    ->label('Created at')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('perform_time')
                    ->label('Performed at')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.client.first_name')
                    ->label('Client')
                    ->searchable(['orders.clients.first_name', 'orders.clients.phone_number']),
                Tables\Columns\TextColumn::make('order.plan.name')
                    ->label('Plan'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('UZS', divideBy: 1)
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('state')
                    ->colors([
                        'danger' => fn ($state) => $state < 0,
                        'warning' => fn ($state) => $state == 1,
                        'success' => fn ($state) => $state == 2,
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        -2 => 'Cancelled (performed)',
                        -1 => 'Cancelled',
                        1 => 'Created',
                        2 => 'Completed',
                        default => $state
                    }),
                Tables\Columns\TextColumn::make('paycom_transaction_id')
                    ->label('Transaction ID')
                    ->toggleable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('paycom_time_datetime')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('perform_time')
                    ->label('Performed')
                    ->dateTime()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reason')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('state')
                    ->options([
                        -2 => 'Cancelled after perform',
                        -1 => 'Cancelled',
                        1 => 'Created',
                        2 => 'Performed',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('paycom_time_datetime', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('paycom_time_datetime', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
            ])
            ->defaultSort('paycom_time_datetime', 'desc');
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

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}