<?php

namespace App\Filament\Resources\WarehouseTransfers;

use App\Filament\Resources\WarehouseTransfers\Pages\CreateWarehouseTransfer;
use App\Filament\Resources\WarehouseTransfers\Pages\EditWarehouseTransfer;
use App\Filament\Resources\WarehouseTransfers\Pages\ListWarehouseTransfers;
use App\Models\WarehouseTransfer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class WarehouseTransferResource extends Resource
{
    protected static ?string $model = WarehouseTransfer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|null $navigationLabel = 'Transfer Gudang';
    protected static ?string $modelLabel = 'Transfer Gudang';
    protected static ?string $pluralModelLabel = 'Transfer Gudang';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventori';
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->columns(12)
            ->schema([
                // Left Column: Metadata & Notes (Span 4)
                \Filament\Schemas\Components\Grid::make(1)
                    ->columnSpan(['default' => 12, 'md' => 4])
                    ->schema([
                        Section::make('Informasi Transfer')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                TextInput::make('number')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->default(fn() => \App\Models\NumberingSetting::getNextNumber('stock_transfer') ?? 'WT/' . date('Ymd') . '/' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT))
                                    ->label('Nomor')
                                    ->extraAttributes(['class' => 'font-mono']),

                                DatePicker::make('date')
                                    ->required()
                                    ->default(now())
                                    ->label('Tanggal'),

                                Select::make('from_warehouse_id')
                                    ->label('Dari Gudang')
                                    ->options(\App\Models\Warehouse::pluck('name', 'id')->prepend('Tanpa Gudang (Unassigned)', 'unassigned'))
                                    ->default(1) // Gudang Utama
                                    ->formatStateUsing(fn($state) => $state === null ? 'unassigned' : $state)
                                    ->dehydrateStateUsing(fn($state) => $state === 'unassigned' ? null : $state)
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->prefixIcon('heroicon-o-arrow-up-tray')
                                    ->prefixIconColor('danger'),

                                Select::make('to_warehouse_id')
                                    ->relationship('toWarehouse', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->label('Ke Gudang')
                                    ->different('from_warehouse_id')
                                    ->prefixIcon('heroicon-o-arrow-down-tray')
                                    ->prefixIconColor('success'),
                            ]),

                        Section::make('Tambahan')
                            ->icon('heroicon-o-pencil-square')
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(3)
                                    ->placeholder('Opsional: Tambahkan catatan transfer...'),
                            ]),
                    ]),

                // Right Column: Items (Span 8)
                Section::make('Daftar Item Transfer')
                    ->icon('heroicon-o-archive-box')
                    ->columnSpan(['default' => 12, 'md' => 8])
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->relationship('product', 'name', modifyQueryUsing: fn($query) => $query->active())
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->label('Produk')
                                    ->columnSpan(['md' => 8]),

                                TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->label('Kuantitas')
                                    ->suffix(fn($get) => \App\Models\Product::find($get('product_id'))?->unit_name ?? 'pcs')
                                    ->columnSpan(['md' => 4])
                                    ->rules(fn(Get $get): array => [
                                        function (string $attribute, $value, Closure $fail) use ($get) {
                                            $fromWarehouseId = $get('../../from_warehouse_id');
                                            $productId = $get('product_id');

                                            if (!$productId)
                                                return;

                                            if (!$fromWarehouseId || $fromWarehouseId === 'unassigned') {
                                                // Check Unassigned Stock (Total Product Stock - Sum of All Warehouse Stocks)
                                                $product = \App\Models\Product::find($productId);
                                                if (!$product)
                                                    return;

                                                $totalAssigned = $product->stocks()->sum('quantity');
                                                $available = $product->stock - $totalAssigned;

                                                // Allow a tiny float margin error if needed, but for now strict
                                                if ($value > $available) {
                                                    $fail("Stok unassigned tidak mencukupi (Tersedia: {$available})");
                                                }
                                            } else {
                                                // Check Specific Warehouse Stock
                                                $stock = \App\Models\Stock::where('warehouse_id', $fromWarehouseId)
                                                    ->where('product_id', $productId)
                                                    ->value('quantity') ?? 0;

                                                if ($value > $stock) {
                                                    $fail("Stok tidak mencukupi (Tersedia: {$stock})");
                                                }
                                            }
                                        },
                                    ]),
                            ])
                            ->columns(12)
                            ->addActionLabel('Tambah Item')
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => (\App\Models\Product::find($state['product_id'])?->name ?? 'Item') . ($state['quantity'] ? " ({$state['quantity']})" : '')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['fromWarehouse', 'toWarehouse']))
            ->columns([
                TextColumn::make('number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('fromWarehouse.name')
                    ->label('Asal')
                    ->placeholder('Tanpa Gudang (Unassigned)')
                    ->sortable(),
                TextColumn::make('toWarehouse.name')
                    ->label('Tujuan')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Item'),
                TextColumn::make('total_qty')
                    ->label('Total Qty')
                    ->getStateUsing(fn(WarehouseTransfer $record): float => $record->items()->sum('quantity'))
                    ->numeric()
                    ->sortable(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouseTransfers::route('/'),
            'create' => CreateWarehouseTransfer::route('/create'),
            'edit' => EditWarehouseTransfer::route('/{record}/edit'),
        ];
    }
}
