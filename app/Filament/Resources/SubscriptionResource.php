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
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.phone_number')
                    ->label('Phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.price')
                    ->label('Price')
                    ->money('UZS', divideBy: 100)
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state->isPast() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('payment_retry_count')
                    ->label('Retries')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Active Status'),
                Tables\Filters\Filter::make('expired')
                    ->query(fn ($query) => $query->where('expires_at', '<', now())),
                Tables\Filters\SelectFilter::make('plan')
                    ->relationship('plan', 'name'),
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
