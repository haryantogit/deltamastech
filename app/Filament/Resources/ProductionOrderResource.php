<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionOrderResource\Pages;
use App\Models\ProductionOrder;
use App\Models\Product;
use App\Models\Account;
use App\Services\StockService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tag as TagModel;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;

class ProductionOrderResource extends Resource
{
    protected static ?string $model = ProductionOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog';
    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationLabel = 'Konversi Produk';
    protected static ?string $modelLabel = 'Konversi Produk';
    protected static ?string $pluralModelLabel = 'Konversi Produk';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(1)
            ->schema([
                Section::make('Informasi Produksi')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Grid::make(1)
                                    ->schema([
                                        Select::make('product_id')
                                            ->label('Produk Hasil')
                                            ->options(Product::where('type', 'manufacturing')->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                if ($state) {
                                                    $product = Product::find($state);

                                                    // Auto-select warehouse if empty
                                                    if (!$get('warehouse_id')) {
                                                        $firstWarehouse = \App\Models\Warehouse::first();
                                                        if ($firstWarehouse) {
                                                            $set('warehouse_id', $firstWarehouse->id);
                                                        }
                                                    }

                                                    $prodQty = (float) ($get('quantity') ?: 1);
                                                    // Load materials
                                                    $materials = $product->productMaterials->map(fn($m) => [
                                                        'product_id' => $m->material_id,
                                                        'quantity' => $m->quantity * $prodQty,
                                                        'unit_name' => $m->material?->unit?->name ?? ($m->material?->unit_name ?: 'Pcs'),
                                                        'unit_price' => $m->material?->cost_of_goods ?? 0,
                                                        'total_price' => ($m->quantity * $prodQty) * ($m->material?->cost_of_goods ?? 0),
                                                    ])->toArray();
                                                    $set('items', $materials);

                                                    // Load overhead costs
                                                    $costs = $product->productionCosts->map(fn($c) => [
                                                        'account_id' => $c->account_id,
                                                        'unit_amount' => $c->unit_amount ?? $c->amount,
                                                        'multiplier' => $c->multiplier ?? 1,
                                                        'amount' => $c->amount,
                                                        'description' => $c->description,
                                                    ])->toArray();
                                                    $set('costs', $costs);

                                                    static::updateTotalCost($set, $materials, $costs, $prodQty);
                                                }
                                            }),
                                        DatePicker::make('transaction_date')
                                            ->label('Tanggal Konversi')
                                            ->default(now())
                                            ->required(),
                                        Select::make('warehouse_id')
                                            ->label('Gudang produk hasil')
                                            ->relationship('warehouse', 'name')
                                            ->required()
                                            ->placeholder('Pilih Gudang')
                                            ->live(),
                                    ])->columnSpan(1),
                                Grid::make(1)
                                    ->schema([
                                        TextInput::make('number')
                                            ->label('Nomor')
                                            ->default(fn() => \App\Models\NumberingSetting::getNextNumber('production_order') ?? 'PC/' . str_pad(\App\Models\ProductionOrder::count() + 1, 5, '0', STR_PAD_LEFT))
                                            ->required()
                                            ->readOnly(),
                                        Select::make('tag')
                                            ->label('Tag')
                                            ->options(TagModel::pluck('name', 'name'))
                                            ->searchable()
                                            ->placeholder('Pilih Tag')
                                            ->live(),
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('quantity')
                                                    ->label('Kuantitas Produksi')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required()
                                                    ->live()
                                                    ->suffixAction(
                                                        Action::make('checkStock')
                                                            ->button()
                                                            ->size('sm')
                                                            ->color(function (Get $get, $state) {
                                                                $productId = $get('product_id');
                                                                $warehouseId = $get('warehouse_id');
                                                                if (!$productId || !$warehouseId)
                                                                    return 'gray';

                                                                $product = Product::find($productId);
                                                                if (!$product)
                                                                    return 'gray';

                                                                $stock = (float) $product->getStockForWarehouse($warehouseId);
                                                                return $stock <= 0 ? 'danger' : 'primary';
                                                            })
                                                            ->label(function (Get $get) {
                                                                $productId = $get('product_id');
                                                                $warehouseId = $get('warehouse_id');
                                                                if (!$productId || !$warehouseId)
                                                                    return '0';
                                                                $product = Product::find($productId);
                                                                if (!$product)
                                                                    return '0';
                                                                return number_format($product->getStockForWarehouse($warehouseId));
                                                            })
                                                    )
                                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                        $productId = $get('product_id');
                                                        if ($productId && $state) {
                                                            $product = Product::find($productId);
                                                            $prodQty = (float) $state;
                                                            $materials = $product->productMaterials->map(fn($m) => [
                                                                'product_id' => $m->material_id,
                                                                'quantity' => ($m->quantity * $prodQty),
                                                                'unit_name' => $m->material?->unit?->name ?? ($m->material?->unit_name ?: 'Pcs'),
                                                                'unit_price' => $m->material?->cost_of_goods ?? 0,
                                                                'total_price' => ($m->quantity * $prodQty) * ($m->material?->cost_of_goods ?? 0),
                                                            ])->toArray();
                                                            $set('items', $materials);
                                                        }
                                                        static::updateTotalCost($set, $get('items'), $get('costs'), $state);
                                                    }),
                                                /* Placeholder::make('selisih_summary')
                                                    ->label('Selisih')
                                                    ->content(function (Get $get) {
                                                        $items = $get('items') ?? [];
                                                        $sync = $get('warehouse_sync');
                                                        $warehouseId = $get('warehouse_id');

                                                        if (!$warehouseId)
                                                            return '0';

                                                        $totalDiff = 0;
                                                        foreach ($items as $item) {
                                                            if (empty($item['product_id']))
                                                                continue;
                                                            $product = Product::find($item['product_id']);
                                                            if (!$product)
                                                                continue;
                                                            $stock = (float) $product->getStockForWarehouse($warehouseId);
                                                            $required = (float) ($item['quantity'] ?? 0);
                                                            $totalDiff += ($stock - $required);
                                                        }

                                                        return number_format($totalDiff);
                                                    }), */
                                            ]),
                                    ])->columnSpan(1),
                            ]),
                        Toggle::make('warehouse_sync')
                            ->label('Gudang bahan baku sama dengan gudang produk hasil')
                            ->default(true)
                            ->live(),
                    ]),

                Section::make('Bahan Baku')
                    ->headerActions([
                        Action::make('refresh_stock')
                            ->label('Refresh Stok')
                            ->icon('heroicon-o-arrow-path')
                            ->action(fn(Set $set, Get $get) => $set('items', $get('items'))),
                    ])
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Produk')
                                    ->relationship('product', 'name')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(4),
                                TextInput::make('quantity')
                                    ->label('Kuantitas')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(2)
                                    ->live(onBlur: true)
                                    ->suffixAction(
                                        Action::make('checkStock')
                                            ->button()
                                            ->size('sm')
                                            ->color(function (Get $get, $state) {
                                                $productId = $get('product_id');
                                                $warehouseId = $get('warehouse_id') ?? $get('../warehouse_id') ?? $get('../../warehouse_id') ?? $get('../../../warehouse_id');
                                                if (!$productId || !$warehouseId)
                                                    return 'gray';

                                                $product = Product::find($productId);
                                                if (!$product)
                                                    return 'gray';

                                                $stock = (float) $product->getStockForWarehouse($warehouseId);
                                                $required = (float) $state;
                                                return ($stock < $required || $stock <= 0) ? 'danger' : 'success';
                                            })
                                            ->label(function (Get $get) {
                                                $productId = $get('product_id');
                                                $warehouseId = $get('warehouse_id') ?? $get('../warehouse_id') ?? $get('../../warehouse_id') ?? $get('../../../warehouse_id');

                                                if (!$productId || !$warehouseId)
                                                    return '0';

                                                $product = Product::find($productId);
                                                if (!$product)
                                                    return '0';

                                                $stock = (float) $product->getStockForWarehouse($warehouseId);
                                                return number_format($stock);
                                            })
                                    )
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $set('total_price', (float) $get('quantity') * (float) $get('unit_price'));
                                    }),
                                TextInput::make('unit_name')
                                    ->label('Satuan')
                                    ->readOnly()
                                    ->columnSpan(2),
                                TextInput::make('unit_price')
                                    ->label('HPP')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(2),
                                TextInput::make('total_price')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(2),
                            ])
                            ->columns(12)
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => static::updateTotalCost($set, $get('items'), $get('costs'), $get('quantity'))),

                        Grid::make(12)
                            ->schema([
                                Placeholder::make('total_label_final')
                                    ->hiddenLabel()
                                    ->content('Total')
                                    ->columnStart(1)
                                    ->columnSpan(4)
                                    ->extraAttributes(['class' => 'font-bold pt-4 text-right']),
                                Placeholder::make('total_quantity_final')
                                    ->hiddenLabel()
                                    ->content(fn(Get $get) => collect($get('items'))->sum('quantity'))
                                    ->columnSpan(2)
                                    ->extraAttributes(['class' => 'font-bold text-center pt-4']),
                            ]),
                    ]),

                Section::make('Biaya produksi terdiri dari')
                    ->schema([
                        Repeater::make('costs')
                            ->relationship('costs')
                            ->schema([
                                Select::make('account_id')
                                    ->label('Akun')
                                    ->relationship('account', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(4),
                                TextInput::make('unit_amount')
                                    ->label('Per Pcs')
                                    ->numeric()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $set('amount', (float) ($get('unit_amount') ?? 0) * (float) ($get('multiplier') ?? 1));
                                    })
                                    ->columnSpan(2),
                                TextInput::make('multiplier')
                                    ->label('Pengali')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $set('amount', (float) ($get('unit_amount') ?? 0) * (float) ($get('multiplier') ?? 1));
                                    })
                                    ->columnSpan(2),
                                TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->readOnly()
                                    ->required()
                                    ->columnSpan(4),
                            ])
                            ->columns(12)
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => static::updateTotalCost($set, $get('items'), $get('costs'), $get('quantity'))),
                        Grid::make(12)
                            ->schema([
                                Placeholder::make('placeholder_1')
                                    ->label('')
                                    ->columnSpan(4),
                                Placeholder::make('total_label')
                                    ->label('')
                                    ->content(new \Illuminate\Support\HtmlString('<strong>Total</strong>'))
                                    ->columnSpan(2),
                                Placeholder::make('total_multiplier')
                                    ->label('')
                                    ->content(fn(Get $get) => collect($get('costs'))->sum('multiplier'))
                                    ->columnSpan(2),
                                Placeholder::make('total_amount_sum')
                                    ->label('')
                                    ->content(fn(Get $get) => 'Rp ' . number_format(collect($get('costs'))->sum('amount'), 2, ',', '.'))
                                    ->columnSpan(4),
                            ])
                            ->visible(fn(Get $get) => count($get('costs') ?? []) > 0),
                    ]),

                Section::make('Ringkasan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('total_cost')
                                    ->label('Biaya total konversi')
                                    ->numeric()
                                    ->readOnly()
                                    ->prefix('Rp'),
                                Placeholder::make('cost_per_unit')
                                    ->label('Biaya per unit')
                                    ->content(fn(Get $get) => 'Rp ' . number_format($get('quantity') > 0 ? (float) $get('total_cost') / (float) $get('quantity') : 0, 2)),
                            ]),
                        Textarea::make('notes')
                            ->label('Keterangan'),
                    ]),
            ]);
    }

    protected static function updateTotalCost(Set $set, $items, $costs, $quantity = 1)
    {
        $materialTotal = collect($items)->sum(fn($i) => ($i['quantity'] ?? 0) * ($i['unit_price'] ?? 0));
        $overheadTotal = collect($costs)->sum(fn($c) => $c['amount'] ?? 0);
        $total = ($materialTotal + $overheadTotal);
        $set('total_cost', $total);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Produk Jadi')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Kuantitas')
                    ->numeric(),
                TextColumn::make('total_cost')
                    ->label('Total Biaya')
                    ->money('IDR'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Done' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                Action::make('complete')
                    ->label('Selesaikan Produksi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'Draft')
                    ->action(function ($record) {
                        \DB::transaction(function () use ($record) {
                            // 1. Deduct Materials
                            foreach ($record->items as $item) {
                                StockService::updateStock(
                                    $item->product_id,
                                    $record->warehouse_id,
                                    -$item->quantity * $record->quantity,
                                    'manufacturing',
                                    ProductionOrder::class,
                                    $record->id,
                                    "Bahan baku untuk produksi {$record->number}"
                                );
                            }

                            // 2. Add Finished Good
                            StockService::updateStock(
                                $record->product_id,
                                $record->warehouse_id,
                                $record->quantity,
                                'manufacturing',
                                ProductionOrder::class,
                                $record->id,
                                "Hasil produksi {$record->number}"
                            );

                            $record->update(['status' => 'Done']);
                        });

                        Notification::make()
                            ->title('Produksi Selesai')
                            ->body("Stok bahan telah dikurangi dan stok produk {$record->product->name} telah bertambah.")
                            ->success()
                            ->send();
                    }),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductionOrders::route('/'),
            'create' => Pages\CreateProductionOrder::route('/create'),
            'edit' => Pages\EditProductionOrder::route('/{record}/edit'),
        ];
    }
}
