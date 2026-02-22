<?php

namespace App\Filament\Resources\SalesQuotations;

use App\Filament\Resources\SalesQuotations\Pages\CreateSalesQuotation;
use App\Filament\Resources\SalesQuotations\Pages\EditSalesQuotation;
use App\Filament\Resources\SalesQuotations\Pages\ListSalesQuotations;
use App\Filament\Resources\SalesQuotations\Schemas\SalesQuotationForm;
use App\Filament\Resources\SalesQuotations\Tables\SalesQuotationsTable;
use App\Models\SalesQuotation;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use BackedEnum;

class SalesQuotationResource extends Resource
{
    protected static ?string $model = SalesQuotation::class;

    protected static string|null $navigationLabel = 'Penawaran Penjualan';
    protected static ?string $pluralModelLabel = 'Penawaran Penjualan';
    protected static bool $shouldRegisterNavigation = false;
    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';
    protected static ?int $navigationSort = 40;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return SalesQuotationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesQuotationsTable::configure($table);
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
            'index' => ListSalesQuotations::route('/'),
            'create' => CreateSalesQuotation::route('/create'),
            'view' => Pages\ViewSalesQuotation::route('/{record}'),
            'edit' => EditSalesQuotation::route('/{record}/edit'),
        ];
    }
}
