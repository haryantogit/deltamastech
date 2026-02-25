<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|null $navigationLabel = 'Riwayat Stok';
    protected static string|\UnitEnum|null $navigationGroup = 'Inventori';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?int $navigationSort = 4;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $modelLabel = 'Riwayat Stok';
    protected static ?string $pluralModelLabel = 'Riwayat Stok';


    public static function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->disabled(),
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('type')
                    ->disabled(),
            ]);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['product', 'warehouse', 'user', 'reference']))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.sku')
                    ->label('Kode Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Gudang')
                    ->sortable()
                    ->placeholder('Tanpa Gudang (Unassigned)'),
                Tables\Columns\TextColumn::make('reference')
                    ->label('No. Referensi')
                    ->formatStateUsing(function ($record) {
                        return $record->reference?->invoice_number ?? $record->reference?->number ?? $record->reference?->id ?? '-';
                    })
                    ->url(function ($record) {
                        if (!$record->reference_id)
                            return null;

                        $resource = match ($record->reference_type) {
                            \App\Models\SalesInvoice::class => \App\Filament\Resources\SalesInvoiceResource::class,
                            \App\Models\PurchaseInvoice::class => \App\Filament\Resources\PurchaseInvoiceResource::class,
                            \App\Models\ManufacturingOrder::class => \App\Filament\Resources\ManufacturingOrderResource::class,
                            \App\Models\WarehouseTransfer::class => \App\Filament\Resources\WarehouseTransfers\WarehouseTransferResource::class,
                            \App\Models\StockAdjustment::class => \App\Filament\Resources\StockAdjustments\StockAdjustmentResource::class,
                            default => null,
                        };

                        if (!$resource)
                            return null;

                        // Only Invoices have View pages enabled currently
                        $page = match ($record->reference_type) {
                            \App\Models\SalesInvoice::class => 'view',
                            \App\Models\PurchaseInvoice::class => 'view',
                            default => 'edit',
                        };

                        return $resource::getUrl($page, ['record' => $record->reference_id]);
                    })
                    ->sortable()
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->wrap()
                    ->limit(50),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'in' => 'Masuk',
                        'out' => 'Keluar',
                        'sale', 'sales', 'Sales' => 'Penjualan',
                        'purchase', 'Purchase' => 'Pembelian',
                        'adjustment' => 'Penyesuaian',
                        'adjustment_plus' => 'Penyesuaian (+)',
                        'adjustment_minus' => 'Penyesuaian (-)',
                        'transfer' => 'Transfer',
                        default => ucfirst($state),
                    })
                    ->colors([
                        'success' => ['in', 'purchase', 'Purchase', 'adjustment_plus'],
                        'danger' => ['out', 'sale', 'sales', 'Sales', 'adjustment_minus'],
                        'info' => ['transfer', 'adjustment'],
                    ]),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable()
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Gudang')
                    ->relationship('warehouse', 'name'),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
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
            'index' => Pages\ListStockMovements::route('/'),
        ];
    }
}
