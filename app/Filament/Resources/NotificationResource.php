<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Pengaturan;
use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Lainnya';
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 8;
    protected static string|null $navigationLabel = 'Notifikasi';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    public static function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form->schema([]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
        ];
    }
}
