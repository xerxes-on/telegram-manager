<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Filament\Resources\AnnouncementResource\RelationManagers;
use App\Models\Announcement;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;
    protected static ?string $modelLabel = 'filament.resources.announcement.label';
    protected static ?string $pluralModelLabel = 'filament.resources.announcement.plural';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('title')
                    ->label(__('filament.announcement.title')),
                TextEntry::make('body')
                    ->label(__('filament.announcement.message')),
                TextEntry::make('status')
                    ->badge()
                    ->label('Status'),
                IconEntry::make('has_attachment')
                    ->boolean()
                    ->label(__('filament.announcement.has_attachment')),
                ImageEntry::make('file_path'),
                TextEntry::make('user.name'),
                TextEntry::make('created_at')
                    ->dateTime(),

            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label(__('filament.announcement.title'))
                    ->required()
                    ->maxLength(255),
                RichEditor::make('body')
                    ->label(__('filament.announcement.message'))
                    ->required()
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'blockquote',
                        'bold',
                        'codeBlock',
                        'italic',
                        'link',
                        'redo',
                        'strike',
                        'underline',
                        'undo',
                    ]),
                Forms\Components\Toggle::make('has_attachment')
                    ->label(__('filament.announcement.has_attachment'))
                    ->default(false)
                    ->live(),

                Forms\Components\FileUpload::make('file_path')
                    ->label(__('filament.announcement.file_path'))
                    ->nullable()
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('announcement-photos')
                    ->visibility('public')
                    ->visible(fn(Get $get): bool => $get('has_attachment') ?? false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('filament.announcement.title'))
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament.announcement.status'))
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('filament.announcement.user_id'))
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_attachment')
                    ->label(__('filament.announcement.has_attachment'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.announcement.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament.announcement.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
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
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('filament.resources.announcement.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.resources.announcement.plural');
    }
}
