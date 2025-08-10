<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('first_name')
                    ->required(),
                Forms\Components\TextInput::make('telegram_id')
                    ->tel()
                    ->required(),
                Forms\Components\TextInput::make('last_name'),
                Forms\Components\TextInput::make('phone_number')
                    ->tel(),
                Forms\Components\TextInput::make('username'),
                Forms\Components\TextInput::make('state'),
                Forms\Components\TextInput::make('chat_id')
                    ->required(),
                Forms\Components\TextInput::make('lang')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Name')
                    ->formatStateUsing(fn ($record) => $record->first_name . ' ' . $record->last_name)
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Telegram')
                    ->formatStateUsing(fn ($state) => $state ? '@' . $state : '-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lang')
                    ->label('Language')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'uz' => 'info',
                        'ru' => 'warning',
                        'en' => 'success',
                        'oz' => 'primary',
                        default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->counts('subscriptions')
                    ->label('Total Subs'),
                Tables\Columns\TextColumn::make('active_subscription')
                    ->label('Active Sub')
                    ->getStateUsing(function ($record) {
                        $activeSub = $record->subscriptions()
                            ->where('status', true)
                            ->where('expires_at', '>', now())
                            ->first();
                        return $activeSub ? $activeSub->plan->name : '-';
                    }),
                Tables\Columns\TextColumn::make('state')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('lang')
                    ->options([
                        'uz' => 'Uzbek',
                        'ru' => 'Russian',
                        'en' => 'English',
                        'oz' => 'Uzbek (Cyrillic)',
                    ]),
                Tables\Filters\Filter::make('has_subscription')
                    ->query(fn ($query) => $query->whereHas('subscriptions')),
                Tables\Filters\Filter::make('active_subscription')
                    ->query(fn ($query) => $query->whereHas('subscriptions', fn ($q) => 
                        $q->where('status', true)->where('expires_at', '>', now()))),
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

    public static function canDeleteAny(): bool
    {
        return false;
    }
    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
