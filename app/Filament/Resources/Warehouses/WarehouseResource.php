<?php

namespace App\Filament\Resources\Warehouses;

use App\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Filament\Resources\Warehouses\Pages\ViewWarehouse;
use App\Models\Warehouse;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront'; // Updated icon to look like a warehouse

    protected static string|null $navigationLabel = 'Gudang';
    protected static ?string $pluralModelLabel = 'Gudang';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventori';
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\Warehouses\Widgets\WarehouseInfoWidget::class,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Gudang')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->label('Kode')
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),
                \Filament\Forms\Components\FileUpload::make('image')
                    ->label('Gambar Gudang')
                    ->image()
                    ->disk('public')
                    ->directory('warehouses')
                    ->visibility('public'),
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Gudang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('stocks_count')
                    ->counts('stocks')
                    ->label('Total Produk'),
                TextColumn::make('total_quantity')
                    ->label('Total Kuantitas')
                    ->getStateUsing(fn(Warehouse $record) => $record->stocks()->sum('quantity'))
                    ->numeric(2)
                    ->summarize(
                        \Filament\Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total')
                            ->using(fn($query) => \App\Models\Stock::whereIn('warehouse_id', $query->pluck('warehouses.id'))->sum('quantity'))
                    ),
                TextColumn::make('total_value')
                    ->label('Total Nilai')
                    ->getStateUsing(function (Warehouse $record) {
                        return \App\Models\Stock::where('warehouse_id', $record->id)
                            ->join('products', 'stocks.product_id', '=', 'products.id')
                            ->sum(\Illuminate\Support\Facades\DB::raw('stocks.quantity * products.buy_price'));
                    })
                    ->money('IDR')
                    ->summarize(
                        \Filament\Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total Asset')
                            ->using(
                                fn($query) => \App\Models\Stock::whereIn('warehouse_id', $query->pluck('warehouses.id'))
                                    ->join('products', 'stocks.product_id', '=', 'products.id')
                                    ->sum(\Illuminate\Support\Facades\DB::raw('stocks.quantity * products.buy_price'))
                            )
                            ->money('IDR')
                    ),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Warehouses\RelationManagers\StocksRelationManager::class,
            \App\Filament\Resources\Warehouses\RelationManagers\StockAdjustmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouses::route('/'),
            'view' => ViewWarehouse::route('/{record}'),
            'edit' => EditWarehouse::route('/{record}/edit'),
        ];
    }
}
