<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource;

use App\Filament\Resources\PurchaseDeliveryResource\Pages;
use App\Models\PurchaseDelivery;
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
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\Placeholder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;

class PurchaseDeliveryResource extends Resource
{
    protected static ?string $model = PurchaseDelivery::class;

    protected static string|null $navigationLabel = 'Pengiriman Pembelian';
    protected static ?string $pluralModelLabel = 'Pengiriman Pembelian';
    protected static bool $shouldRegisterNavigation = false;
    protected static string|\UnitEnum|null $navigationGroup = 'Pembelian';
    protected static ?int $navigationSort = 30;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Informasi Utama')
                            ->schema([
                                // Row 1: Vendor, Nomor, Nomor Pesanan
                                Grid::make(3)
                                    ->schema([
                                        Hidden::make('supplier_id'),
                                        Select::make('supplier_id_select')
                                            ->relationship('supplier', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->label('Vendor')
                                            ->hidden(fn(Get $get) => filled($get('purchase_order_id')))
                                            ->live()
                                            ->afterStateUpdated(fn($state, Set $set) => $set('supplier_id', $state)),
                                        TextInput::make('supplier_name')
                                            ->label('Vendor')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->hidden(fn(Get $get) => !filled($get('purchase_order_id'))),

                                        TextInput::make('number')
                                            ->required()
                                            ->label('Nomor')
                                            ->default(fn() => \App\Models\NumberingSetting::getNextNumber('purchase_delivery') ?? 'PD/' . str_pad(\App\Models\PurchaseDelivery::count() + 1, 5, '0', STR_PAD_LEFT))
                                            ->disabled()
                                            ->dehydrated(),

                                        Hidden::make('purchase_order_id'),
                                        Select::make('purchase_order_id_select')
                                            ->relationship('purchaseOrder', 'number')
                                            ->label('Nomor Pemesanan')
                                            ->searchable()
                                            ->preload()
                                            ->hidden(fn(Get $get) => filled($get('purchase_order_id')))
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if (!$state)
                                                    return;
                                                $set('purchase_order_id', $state);
                                                $po = \App\Models\PurchaseOrder::with(['items.product', 'items.unit', 'tags', 'supplier', 'warehouse', 'shippingMethod'])->find($state);
                                                if (!$po)
                                                    return;
                                                $set('supplier_id', $po->supplier_id);
                                                $set('supplier_name', $po->supplier?->name ?? '');
                                                $set('warehouse_id', $po->warehouse_id);
                                                $set('warehouse_name', $po->warehouse?->name ?? '');
                                                $set('purchase_order_number', $po->number);
                                                $set('reference', $po->number);
                                                $set('date', $po->date);
                                                $set('shipping_date', $po->shipping_date);
                                                $set('shipping_method_id', $po->shipping_method_id);
                                                $set('tracking_number', $po->tracking_number);
                                                $tagIds = $po->tags->pluck('id')->toArray();
                                                if (!empty($tagIds))
                                                    $set('tags', $tagIds);
                                                $po->load('deliveries.items');
                                                $deliveredQuantities = [];
                                                foreach ($po->deliveries as $delivery) {
                                                    foreach ($delivery->items as $dItem) {
                                                        $key = $dItem->product_id . '-' . $dItem->unit_id;
                                                        $deliveredQuantities[$key] = ($deliveredQuantities[$key] ?? 0) + $dItem->quantity;
                                                    }
                                                }
                                                $items = [];
                                                foreach ($po->items as $item) {
                                                    $key = $item->product_id . '-' . $item->unit_id;
                                                    $remaining = max(0, $item->quantity - ($deliveredQuantities[$key] ?? 0));
                                                    if ($remaining > 0) {
                                                        $items[] = [
                                                            'product_id' => $item->product_id,
                                                            'product_name' => $item->product?->name ?? '',
                                                            'description' => $item->description,
                                                            'quantity' => $remaining,
                                                            'unit_id' => $item->unit_id,
                                                            'unit_name' => $item->unit?->name ?? '',
                                                        ];
                                                    }
                                                }
                                                $set('items', !empty($items) ? $items : (count($po->items) > 0 ? [] : []));
                                            }),
                                        TextInput::make('purchase_order_number')
                                            ->label('Nomor Pemesanan')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->hidden(fn(Get $get) => !filled($get('purchase_order_id')))
                                            ->suffixAction(
                                                fn($state) => $state ? Action::make('view_po')
                                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                                    ->url(fn(Get $get) => $get('purchase_order_id') ? PurchaseOrderResource::getUrl('view', ['record' => $get('purchase_order_id')]) : null)
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
                                            ->hidden(fn(Get $get) => filled($get('purchase_order_id')))
                                            ->live()
                                            ->afterStateUpdated(fn($state, Set $set) => $set('warehouse_id', $state)),
                                        TextInput::make('warehouse_name')
                                            ->label('Gudang')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->hidden(fn(Get $get) => !filled($get('purchase_order_id'))),

                                        TextInput::make('reference')
                                            ->label('Referensi')
                                            ->maxLength(255),

                                        Select::make('tags')
                                            ->relationship('tags', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->label('Tag'),
                                    ]),

                            ]),

                        Section::make('Informasi Pengiriman')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        DatePicker::make('date')
                                            ->label('Tanggal Penerimaan')
                                            ->nullable(),

                                        DatePicker::make('shipping_date')
                                            ->label('Tanggal Pengiriman')
                                            ->default(now()),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        Select::make('shipping_method_id')
                                            ->relationship('shippingMethod', 'name')
                                            ->label('Ekspedisi')
                                            ->createOptionForm([
                                                TextInput::make('name')->label('Nama Ekspedisi')->required(),
                                            ])
                                            ->searchable()
                                            ->preload(),
                                        TextInput::make('tracking_number')
                                            ->label('No. Resi'),
                                    ]),
                            ]),

                        Section::make('Item Pengiriman')
                            ->schema([
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
                                                    ->hidden(fn(Get $get) => !filled($get('../../purchase_order_id'))),
                                                Select::make('product_id_select')
                                                    ->relationship('product', 'name', modifyQueryUsing: fn($query) => $query->active())
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->label('Produk')
                                                    ->columnSpan(3)
                                                    ->hidden(fn(Get $get) => filled($get('../../purchase_order_id')))
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
                                                    ->live(debounce: 500)
                                                    ->suffixAction(
                                                        Action::make('checkStock')
                                                            ->button()
                                                            ->size('sm')
                                                            ->color(function (Get $get, $state) {
                                                                $productId = $get('product_id');
                                                                $warehouseId = $get('warehouse_id') ?? $get('../warehouse_id') ?? $get('../../warehouse_id') ?? $get('../../../warehouse_id');
                                                                if (!$warehouseId) {
                                                                    $warehouseId = $get('../../warehouse_id_select') ?? $get('warehouse_id_select');
                                                                }

                                                                if (!$productId || !$warehouseId)
                                                                    return 'gray';

                                                                $product = \App\Models\Product::find($productId);
                                                                if (!$product || !$product->track_inventory)
                                                                    return 'gray';

                                                                $stock = (float) $product->getStockForWarehouse($warehouseId);
                                                                $requestedQty = (float) $state;
                                                                return ($stock < $requestedQty || $stock <= 0) ? 'danger' : 'success';
                                                            })
                                                            ->label(function (Get $get) {
                                                                $productId = $get('product_id');
                                                                $warehouseId = $get('warehouse_id') ?? $get('../warehouse_id') ?? $get('../../warehouse_id') ?? $get('../../../warehouse_id');
                                                                if (!$warehouseId) {
                                                                    $warehouseId = $get('../../warehouse_id_select') ?? $get('warehouse_id_select');
                                                                }

                                                                if (!$productId || !$warehouseId)
                                                                    return '0';

                                                                $product = \App\Models\Product::find($productId);
                                                                if (!$product || !$product->track_inventory)
                                                                    return '0';

                                                                $stock = $product->getStockForWarehouse($warehouseId);
                                                                return number_format($stock);
                                                            })
                                                    ),

                                                Hidden::make('unit_id'),
                                                TextInput::make('unit_name')
                                                    ->label('Satuan')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->columnSpan(3)
                                                    ->hidden(fn(Get $get) => !filled($get('../../purchase_order_id'))),
                                                Select::make('unit_id_select')
                                                    ->relationship('unit', 'name')
                                                    ->label('Satuan')
                                                    ->placeholder('Pilih')
                                                    ->searchable()
                                                    ->preload()
                                                    ->columnSpan(3)
                                                    ->hidden(fn(Get $get) => filled($get('../../purchase_order_id')))
                                                    ->live()
                                                    ->afterStateUpdated(fn($state, Set $set) => $set('unit_id', $state)),
                                            ]),
                                    ])
                                    ->defaultItems(1)
                                    ->addActionLabel('Tambah Item')
                                    ->label('Item Pengiriman')
                                    ->addable(fn(Get $get) => !filled($get('purchase_order_id')))
                                    ->deletable(fn(Get $get) => !filled($get('purchase_order_id')))
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
                                            ->directory('purchase-deliveries')
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
                    ])->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        // 1. Header Grid (3 Columns)
                        Grid::make(3)
                            ->schema([
                                // Column 1: Vendor Details
                                Group::make([
                                    TextEntry::make('supplier.name')
                                        ->label('VENDOR')
                                        ->weight('bold')
                                        ->color('primary')
                                        ->size('lg'),
                                    TextEntry::make('supplier.company')
                                        ->label('')
                                        ->icon('heroicon-m-building-office')
                                        ->visible(fn($record) => filled($record->supplier?->company)),
                                    TextEntry::make('supplier.phone')
                                        ->label('')
                                        ->icon('heroicon-m-phone')
                                        ->visible(fn($record) => filled($record->supplier?->phone)),
                                    TextEntry::make('supplier.address')
                                        ->label('')
                                        ->icon('heroicon-m-map-pin')
                                        ->visible(fn($record) => filled($record->supplier?->address)),
                                ])->columnSpan(1),

                                // Column 2: Order/Date Details
                                Group::make([
                                    TextEntry::make('number')->label('NOMOR'),
                                    TextEntry::make('shipping_date')->label('TGL PENGIRIMAN')->date('d/m/Y'),
                                    TextEntry::make('reference')
                                        ->label('REFERENSI')
                                        ->default(fn($record) => $record->purchaseOrder?->number ?? $record->reference),
                                ])->columnSpan(1),

                                // Column 3: Contextual Details
                                Group::make([
                                    TextEntry::make('date')->label('TGL PENERIMAAN')->date('d/m/Y'),
                                    TextEntry::make('warehouse.name')
                                        ->label('GUDANG')
                                        ->default('Unassigned')
                                        ->color('primary'),
                                    TextEntry::make('tags.name')
                                        ->label('TAG')
                                        ->badge()
                                        ->separator(','),
                                ])->columnSpan(1),
                            ]),

                        // 2. Items Table
                        Section::make()
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->label('')
                                    ->schema([
                                        Grid::make(9) // Match PO's 9-column grid for consistency
                                            ->schema([
                                                TextEntry::make('product.name')
                                                    ->label('PRODUK')
                                                    ->formatStateUsing(fn($record) => $record->product?->sku . ' - ' . $record->product?->name)
                                                    ->color('primary')
                                                    ->columnSpan(2),
                                                TextEntry::make('description')
                                                    ->label('DESKRIPSI')
                                                    ->default('-')
                                                    ->columnSpan(2),
                                                TextEntry::make('quantity')
                                                    ->label('KUANTITAS')
                                                    ->alignRight()
                                                    ->columnSpan(1),
                                                TextEntry::make('unit.name')
                                                    ->label('SATUAN')
                                                    ->columnSpan(1),
                                                TextEntry::make('status')
                                                    ->label('STATUS')
                                                    ->badge()
                                                    ->state(fn($record) => $record->delivery->status)
                                                    ->formatStateUsing(fn(string $state): string => match ($state) {
                                                        'draft' => 'Draf',
                                                        'pending' => 'Menunggu',
                                                        'received' => 'Diterima',
                                                        'cancelled' => 'Dibatalkan',
                                                        default => $state,
                                                    })
                                                    ->color(fn(string $state): string => match ($state) {
                                                        'draft' => 'gray',
                                                        'pending' => 'info',
                                                        'received' => 'success',
                                                        'cancelled' => 'danger',
                                                        default => 'gray',
                                                    })
                                                    ->columnSpan(1),
                                                // Empty spacers to align with PO's price/tax columns
                                                TextEntry::make('spacer_price')->label('')->state('')->columnSpan(1),
                                                TextEntry::make('spacer_total')->label('')->state('')->columnSpan(1),
                                            ]),
                                    ]),

                                // Footer: Total Quantity
                                Grid::make(9)
                                    ->schema([
                                        TextEntry::make('total_quantity')
                                            ->label('Total Kuantitas')
                                            ->state(fn($record) => $record->items?->sum('quantity') ?? 0)
                                            ->weight('bold')
                                            ->alignRight()
                                            ->columnStart(5)
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->compact(),

                        // 3. Summary Section
                        Grid::make(2)
                            ->schema([
                                Group::make([
                                    TextEntry::make('notes')
                                        ->label('Pesan')
                                        ->default('-'),
                                ])->columnSpan(1),
                                Group::make([
                                    Grid::make(2)
                                        ->schema([
                                            TextEntry::make('shipping_cost')
                                                ->label('Biaya pengiriman')
                                                ->money('IDR')
                                                ->alignRight()
                                                ->visible(fn($record) => $record->shipping_cost > 0),
                                            TextEntry::make('spacer6')->label('')->state('')->hidden()
                                                ->visible(fn($record) => $record->shipping_cost > 0),

                                            TextEntry::make('total_amount_display')
                                                ->label('Total')
                                                ->state(fn($record) => (float) $record->shipping_cost)
                                                ->money('IDR')
                                                ->weight('bold')
                                                ->size('lg')
                                                ->alignRight(),
                                            TextEntry::make('spacer7')->label('')->state('')->hidden(),
                                        ])
                                        ->columns(2),
                                ])->columnSpan(1),
                            ]),
                    ]),

                // 4. Related Transactions (Grid at the bottom)
                Section::make('Transaksi Terkait')
                    ->schema([
                        ViewEntry::make('transactions')
                            ->label('')
                            ->view('filament.infolists.purchase-delivery-transactions'),
                    ])
                    ->visible(fn($record) => $record->purchase_order_id !== null)
                    ->collapsible(),

                // 5. Audit Log
                Section::make('')
                    ->schema([
                        TextEntry::make('updated_at')
                            ->label('Pantau log perubahan data')
                            ->formatStateUsing(fn($record) => 'Terakhir diubah oleh system pada ' . $record->updated_at->format('d M Y H:i'))
                            ->columnSpanFull(),
                    ])
                    ->compact(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['supplier', 'purchaseOrder.paymentTerm', 'warehouse', 'tags', 'shippingMethod']))
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn($record) => self::getUrl('view', ['record' => $record])),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Vendor')
                    ->sortable()
                    ->description(fn($record) => $record->supplier?->company),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Referensi')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Nama Gudang')
                    ->sortable()
                    ->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseOrder.paymentTerm.name')
                    ->label('Termin')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('tags.name')
                    ->label('Tag')
                    ->badge()
                    ->separator(','),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Draf',
                        'pending' => 'Menunggu',
                        'received' => 'Diterima',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'info',
                        'received' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
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
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name', modifyQueryUsing: fn($query) => $query->where('type', 'vendor'))
                    ->label('Pemasok')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Menunggu',
                        'received' => 'Diterima',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn($record) => $record->status === 'draft'),
                    Action::make('confirm')
                        ->label('Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('info')
                        ->action(function ($record) {
                            $updateData = ['status' => 'pending'];
                            if (!$record->shipping_date) {
                                $updateData['shipping_date'] = now();
                            }
                            $record->update($updateData);
                        })
                        ->visible(fn($record) => $record->status === 'draft')
                        ->requiresConfirmation(),
                    Action::make('receive')
                        ->label('Terima Barang')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('success')
                        ->action(fn($record) => $record->update([
                            'status' => 'received',
                            'date' => now(),
                        ]))
                        ->visible(fn($record) => $record->status === 'pending')
                        ->requiresConfirmation(),
                    Action::make('cancel')
                        ->label('Batalkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn($record) => $record->update(['status' => 'cancelled']))
                        ->visible(fn($record) => in_array($record->status, ['draft', 'pending']))
                        ->requiresConfirmation(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPurchaseDeliveries::route('/'),
            'create' => Pages\CreatePurchaseDelivery::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditPurchaseDelivery::route('/{record}/edit'),
        ];
    }
}
