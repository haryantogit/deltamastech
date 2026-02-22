<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesDeliveryResource\Pages;
use App\Models\SalesDelivery;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\SalesOrderResource;

class SalesDeliveryResource extends Resource
{
    protected static ?string $model = SalesDelivery::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';
    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';
    protected static ?int $navigationSort = 30;
    protected static string|null $navigationLabel = 'Pengiriman Penjualan';
    protected static ?string $pluralModelLabel = 'Pengiriman Penjualan';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(1)
            ->schema([
                Section::make('Informasi Utama')
                    ->schema([
                        // Row 1: Pelanggan, Nomor, Nomor Pesanan
                        Grid::make(3)
                            ->schema([
                                Select::make('customer_id_select')
                                    ->relationship('customer', 'name')
                                    ->required()
                                    ->label('Pelanggan')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->hidden(fn(Get $get) => filled($get('sales_order_id')))
                                    ->afterStateUpdated(fn($state, Set $set) => $set('customer_id', $state)),
                                TextInput::make('customer_name')
                                    ->label('Pelanggan')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hidden(fn(Get $get) => !filled($get('sales_order_id'))),
                                Hidden::make('customer_id'),
                                TextInput::make('number')
                                    ->required()
                                    ->label('Nomor')
                                    ->default(fn() => \App\Models\SalesDelivery::generateNumber())
                                    ->readOnly()
                                    ->dehydrated(),
                                Hidden::make('sales_order_id'),
                                TextInput::make('sales_order_number')
                                    ->label('Nomor Pesanan')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hidden(fn(Get $get) => !filled($get('sales_order_id')))
                                    ->suffixAction(
                                        fn($state) => $state ? Action::make('view_so')
                                            ->icon('heroicon-m-arrow-top-right-on-square')
                                            ->url(fn(Get $get) => $get('sales_order_id') ? SalesOrderResource::getUrl('view', ['record' => $get('sales_order_id')]) : null)
                                            ->openUrlInNewTab() : null
                                    ),
                            ]),

                        // Row 3: Gudang, Referensi, Tag
                        Grid::make(3)
                            ->schema([
                                Hidden::make('warehouse_id'),
                                Select::make('warehouse_id_select')
                                    ->relationship('warehouse', 'name')
                                    ->label('Gudang')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->hidden(fn(Get $get) => filled($get('sales_order_id')))
                                    ->live()
                                    ->afterStateUpdated(fn($state, Set $set) => $set('warehouse_id', $state)),
                                TextInput::make('warehouse_name')
                                    ->label('Gudang')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hidden(fn(Get $get) => !filled($get('sales_order_id'))),
                                TextInput::make('reference')
                                    ->label('Referensi'),
                                Select::make('tags')
                                    ->relationship('tags', 'name')
                                    ->multiple()
                                    ->label('Tag')
                                    ->createOptionForm([
                                        TextInput::make('name')->label('Nama Tag')->required(),
                                    ])
                                    ->preload(),
                            ]),
                    ]),

                Section::make('Informasi Pengiriman')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('date')
                                    ->required()
                                    ->label('Tanggal Pengiriman')
                                    ->default(now()),
                                Select::make('shipping_method_id')
                                    ->relationship('shippingMethod', 'name')
                                    ->label('Ekspedisi')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                    ]),
                                TextInput::make('tracking_number')
                                    ->label('No. Resi'),
                            ]),
                    ]),

                Section::make('Item Pengiriman')
                    ->schema([
                        TextInput::make('barcode_scanner')
                            ->label('Scan Barcode/SKU')
                            ->placeholder('Scan Barcode/SKU...')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (blank($state))
                                    return;
                                $product = \App\Models\Product::where('sku', trim($state))->first();
                                if ($product) {
                                    $items = $get('items') ?? [];
                                    $items[] = [
                                        'product_id' => $product->id,
                                        'product_name' => $product->name,
                                        'description' => $product->description,
                                        'quantity' => 1,
                                        'unit_id' => $product->unit_id,
                                        'unit_name' => $product->unit?->name ?? '',
                                    ];
                                    $set('items', $items);
                                    $set('barcode_scanner', null);
                                }
                            })
                            ->columnSpanFull(),

                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Grid::make(11)
                                    ->schema([
                                        Hidden::make('product_id'),
                                        TextInput::make('product_name')
                                            ->label('Produk')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpan(3)
                                            ->visible(fn(Get $get) => filled($get('../../sales_order_id'))),
                                        Select::make('product_id_select')
                                            ->relationship('product', 'name', modifyQueryUsing: fn($query) => $query->active())
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->label('Produk')
                                            ->columnSpan(3)
                                            ->visible(fn(Get $get) => !filled($get('../../sales_order_id')))
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                $set('product_id', $state);
                                                if ($product = \App\Models\Product::with('unit')->find($state)) {
                                                    $set('description', $product->description);
                                                    $set('unit_id', $product->unit_id);
                                                    $set('unit_name', $product->unit?->name ?? '');
                                                }
                                            }),

                                        Textarea::make('description')
                                            ->label('Deskripsi')
                                            ->rows(1)
                                            ->columnSpan(3),

                                        TextInput::make('quantity')
                                            ->numeric()
                                            ->required()
                                            ->label('Kuantitas')
                                            ->default(1)
                                            ->columnSpan(2)
                                            ->hint(function (Get $get) {
                                                $productId = $get('product_id');
                                                $warehouseId = $get('../../warehouse_id');
                                                if (!$productId)
                                                    return null;

                                                $product = \App\Models\Product::find($productId);
                                                if (!$product || !$product->track_inventory)
                                                    return null;

                                                $stock = $product->getStockForWarehouse($warehouseId);
                                                $requestedQty = (float) $get('quantity');
                                                $color = $stock >= $requestedQty ? '#22c55e' : '#ef4444';
                                                return new \Illuminate\Support\HtmlString("<span style='background-color: {$color}; color: white; padding: 2px 8px; font-size: 0.75rem; font-weight: bold; border-radius: 9999px;'>{$stock}</span>");
                                            }),

                                        Hidden::make('unit_id'),
                                        TextInput::make('unit_name')
                                            ->label('Satuan')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpan(3)
                                            ->visible(fn(Get $get) => filled($get('../../sales_order_id'))),
                                        Select::make('unit_id_select')
                                            ->relationship('unit', 'name')
                                            ->label('Satuan')
                                            ->placeholder('Pilih')
                                            ->searchable()
                                            ->preload()
                                            ->columnSpan(3)
                                            ->visible(fn(Get $get) => !filled($get('../../sales_order_id')))
                                            ->live()
                                            ->afterStateUpdated(fn($state, Set $set) => $set('unit_id', $state)),
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Item')
                            ->label('Item Pengiriman')
                            ->addable(fn(Get $get) => !filled($get('sales_order_id')))
                            ->deletable(fn(Get $get) => !filled($get('sales_order_id')))
                            ->columnSpanFull(),
                    ]),

                Grid::make(2)
                    ->schema([
                        Group::make()
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Pesan')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                FileUpload::make('attachments')
                                    ->label('Attachment')
                                    ->multiple()
                                    ->directory('sales-deliveries')
                                    ->columnSpanFull(),
                            ])->columnSpan(1),

                        Group::make()
                            ->schema([
                                TextInput::make('shipping_cost')
                                    ->label('Biaya pengiriman')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0),
                            ])->columnSpan(1),
                    ]),

            ]);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->columns(1)
            ->schema([
                Section::make('Informasi Pengiriman')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('customer.name')
                                    ->label('Pelanggan')
                                    ->columnSpan(2),
                                TextEntry::make('number')->label('Nomor Pengiriman'),
                                TextEntry::make('date')->label('Tanggal')->date('d/m/Y'),
                                TextEntry::make('salesOrder.number')->label('No. Pesanan'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn(string $state): string => match (strtolower($state)) {
                                        'draft' => 'Draft',
                                        'shipped' => 'Dikirim',
                                        'delivered' => 'Terkirim',
                                        'packing' => 'Pengemasan',
                                        'pending' => 'Menunggu',
                                        'cancelled' => 'Dibatalkan',
                                        default => $state,
                                    })
                                    ->color(fn(string $state): string => match (strtolower($state)) {
                                        'draft' => 'gray',
                                        'shipped' => 'primary',
                                        'delivered' => 'success',
                                        'packing' => 'warning',
                                        'pending' => 'danger',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('warehouse.name')->label('Gudang'),
                                TextEntry::make('reference')->label('Referensi'),
                                TextEntry::make('shippingMethod.name')->label('Ekspedisi'),
                                TextEntry::make('tracking_number')->label('No. Resi'),
                                TextEntry::make('shipping_cost')->label('Biaya Pengiriman')->money('IDR'),
                            ]),
                    ]),

                Section::make('Daftar Produk')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('product.name')->label('Produk')->columnSpan(1),
                                        TextEntry::make('description')->label('Deskripsi')->default('-')->columnSpan(1),
                                        TextEntry::make('quantity')->label('Jumlah')->alignCenter()->columnSpan(1),
                                        TextEntry::make('unit.name')->label('Satuan')->alignCenter()->columnSpan(1),
                                    ]),
                            ]),
                    ]),

                Section::make('')
                    ->schema([
                        TextEntry::make('notes')->label('Catatan')->default('-')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['customer', 'salesOrder', 'warehouse']))
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->searchable()
                    ->label('Nomor')
                    ->sortable()
                    ->color('primary')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->sortable()
                    ->label('Pelanggan'),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Referensi')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Gudang')
                    ->sortable()
                    ->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Tanggal'),
                Tables\Columns\TextColumn::make('tags.name')
                    ->label('Tag')
                    ->badge()
                    ->separator(','),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match (strtolower($state)) {
                        'draft' => 'Draft',
                        'shipped' => 'Dikirim',
                        'delivered' => 'Terkirim',
                        'packing' => 'Pengemasan',
                        'pending' => 'Menunggu',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'draft' => 'gray',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'packing' => 'warning',
                        'pending' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->label('Status')
                    ->sortable(),
                Tables\Columns\TextColumn::make('shippingMethod.name')
                    ->label('Ekspedisi')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('shipping_cost')
                    ->label('Biaya Kirim')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name', modifyQueryUsing: fn($query) => $query->where('type', 'customer'))
                    ->label('Pelanggan')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Menunggu',
                        'shipped' => 'Dikirim',
                        'delivered' => 'Terkirim',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->label('Status'),
            ])
            ->defaultSort('date', 'desc')
            ->actions([
                ActionGroup::make([
                    Action::make('deliver')
                        ->label('Barang Terkirim')
                        ->icon('heroicon-o-truck')
                        ->color('success')
                        ->action(fn($record) => $record->update([
                            'status' => 'delivered',
                            'date' => now(),
                        ]))
                        ->visible(fn($record) => in_array($record->status, ['draft', 'pending']))
                        ->requiresConfirmation(),
                    Action::make('cancel')
                        ->label('Batalkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn($record) => $record->update(['status' => 'cancelled']))
                        ->visible(fn($record) => in_array($record->status, ['draft']))
                        ->requiresConfirmation(),
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
            'index' => Pages\ListSalesDeliveries::route('/'),
            'create' => Pages\CreateSalesDelivery::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditSalesDelivery::route('/{record}/edit'),
        ];
    }
}
