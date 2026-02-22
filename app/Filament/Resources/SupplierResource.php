<?php

namespace App\Filament\Resources;

use App\Models\Contact;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms;
use Filament\Schemas\Schema;

class SupplierResource extends ContactResource
{
    protected static ?string $model = Contact::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|null $navigationLabel = 'Supplier';
    protected static string|\UnitEnum|null $navigationGroup = 'Pembelian';
    protected static ?int $navigationSort = 1;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(fn($query) => $query->whereIn('type', ['vendor', 'both']));
    }
}
