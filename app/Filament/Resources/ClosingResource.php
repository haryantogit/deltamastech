<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClosingResource\Pages\CreateClosing;
use App\Filament\Resources\ClosingResource\Pages\EditClosing;
use App\Filament\Resources\ClosingResource\Pages\ListClosings;
use App\Filament\Resources\ClosingResource\Schemas\ClosingForm;
use App\Filament\Resources\ClosingResource\Tables\ClosingsTable;
use App\Models\Closing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClosingResource extends Resource
{
    protected static ?string $model = Closing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return ClosingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClosingsTable::configure($table);
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
            'index' => ListClosings::route('/'),
            'create' => CreateClosing::route('/create'),
            'edit' => EditClosing::route('/{record}/edit'),
        ];
    }
}
