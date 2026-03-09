<?php

namespace App\Filament\Resources;

use App\Models\Contact;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms;
use Filament\Schemas\Schema;

class PelangganResource extends Resource
{
    protected static ?string $model = Contact::class;

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_hub_penjualan');
    }

    protected static bool $shouldRegisterNavigation = false;

    protected static string|null $navigationLabel = 'Pelanggan';
    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';
    protected static ?int $navigationSort = 1;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(fn($query) => $query->whereIn('type', ['customer', 'both']));
    }
}
