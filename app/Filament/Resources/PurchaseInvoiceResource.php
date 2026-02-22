<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseInvoiceResource\Pages;
use App\Models\PurchaseInvoice;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Action;
use Filament\Forms;
use App\Filament\Resources\PurchaseOrderResource;

class PurchaseInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseInvoice::class;

    protected static string|null $navigationLabel = 'Tagihan Pembelian';
    protected static ?string $pluralModelLabel = 'Tagihan Pembelian';
    protected static bool $shouldRegisterNavigation = false;
    protected static string|\UnitEnum|null $navigationGroup = 'Pembelian';
    protected static ?int $navigationSort = 20;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Informasi Utama')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('supplier_id_select')
                                            ->relationship('supplier', 'name', modifyQueryUsing: fn($query) => $query->where('type', 'vendor'))
                                            ->label('Vendor')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->getOptionLabelUsing(fn($value) => \App\Models\Contact::find($value)?->name ?? $value)
                                            ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('purchase_order_id')))
                                            ->live()
                                            ->afterStateUpdated(fn($state, Set $set) => $set('supplier_id', $state)),
                                        TextInput::make('supplier_name')
                                            ->label('Vendor')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->hidden(fn(Get $get, string $operation) => $operation !== 'create' || !filled($get('purchase_order_id'))),


                                        TextInput::make('number')
                                            ->label('Nomor')
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->default(function () {
                                                $last = \App\Models\PurchaseInvoice::latest('id')->first();
                                                if ($last && preg_match('/PI\/(\d{5})/', $last->number, $matches)) {
                                                    return 'PI/' . str_pad(intval($matches[1]) + 1, 5, '0', STR_PAD_LEFT);
                                                }
                                                return 'PI/00001';
                                            }),
                                        DatePicker::make('date')
                                            ->label('Tgl. Transaksi')
                                            ->required()
                                            ->default(now()),
                                        DatePicker::make('due_date')
                                            ->label('Tgl. Jatuh Tempo')
                                            ->default(now()->addDays(30)),
                                        Select::make('payment_term_id')
                                            ->relationship('paymentTerm', 'name')
                                            ->label('Termin')
                                            ->createOptionForm([
                                                TextInput::make('name')->required(),
                                                TextInput::make('days')->numeric()->required(),
                                            ]),
                                        Select::make('purchase_order_id_select')
                                            ->relationship('purchaseOrder', 'number')
                                            ->label('Nomor Pesanan')
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('purchase_order_id')))
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (!$state)
                                                    return;
                                                $set('purchase_order_id', $state);
                                                $po = \App\Models\PurchaseOrder::with(['supplier', 'warehouse', 'shippingMethod', 'items.product', 'items.unit'])->find($state);
                                                if (!$po)
                                                    return;

                                                $set('supplier_id', $po->supplier_id);
                                                $set('warehouse_id', $po->warehouse_id);
                                                $set('payment_term_id', $po->payment_term_id);
                                                $set('reference', $po->reference);
                                                $set('shipping_method_id', $po->shipping_method_id);
                                                $set('tracking_number', $po->tracking_number);
                                                $set('tax_inclusive', (bool) $po->tax_inclusive);
                                                $set('discount_amount', $po->discount_amount);
                                                $set('shipping_cost', $po->shipping_cost);
                                                $set('other_cost', $po->other_cost);
                                                $set('down_payment', $po->down_payment);
                                                $set('notes', $po->notes);

                                                $items = $po->items->map(fn($item) => [
                                                    'product_id' => $item->product_id,
                                                    'product_name' => $item->product?->name ?? '-',
                                                    'description' => $item->description,
                                                    'quantity' => $item->quantity,
                                                    'unit_id' => $item->unit_id,
                                                    'unit_name' => $item->unit?->name ?? '-',
                                                    'unit_price' => $item->unit_price,
                                                    'discount_percent' => $item->discount_percent,
                                                    'tax_name' => $item->tax_name,
                                                    'total_price' => $item->total_price,
                                                ])->toArray();

                                                $set('items', $items);
                                                self::updateTotals($get, $set);
                                            }),
                                        TextInput::make('purchase_order_number')
                                            ->label('Nomor Pesanan')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->hidden(fn(Get $get, string $operation) => $operation !== 'create' || !filled($get('purchase_order_id')))
                                            ->suffixAction(
                                                fn($state) => $state ? Action::make('view_po')
                                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                                    ->url(fn(Get $get) => $get('purchase_order_id') ? PurchaseOrderResource::getUrl('view', ['record' => $get('purchase_order_id')]) : null)
                                                    ->openUrlInNewTab() : null
                                            ),


                                        Select::make('warehouse_id_select')
                                            ->relationship('warehouse', 'name')
                                            ->label('Gudang')
                                            ->searchable()
                                            ->preload()
                                            ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('purchase_order_id')))
                                            ->live()
                                            ->afterStateUpdated(fn($state, Set $set) => $set('warehouse_id', $state)),
                                        TextInput::make('warehouse_name')
                                            ->label('Gudang')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->hidden(fn(Get $get, string $operation) => $operation !== 'create' || !filled($get('purchase_order_id'))),

                                        TextInput::make('reference')
                                            ->label('Referensi'),
                                        Select::make('tags')
                                            ->relationship('tags', 'name')
                                            ->multiple()
                                            ->label('Tag')
                                            ->preload(),
                                        Hidden::make('purchase_order_id'),
                                        Hidden::make('supplier_id'),
                                        Hidden::make('warehouse_id'),
                                        Hidden::make('shipping_method_id'),
                                    ]),
                            ]),

                        Section::make('Informasi Pengiriman')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        DatePicker::make('shipping_date')
                                            ->label('Tanggal Pengiriman'),
                                        Select::make('shipping_method_id')
                                            ->relationship('shippingMethod', 'name')
                                            ->label('Ekspedisi')
                                            ->searchable()
                                            ->preload()
                                            ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('purchase_order_id')))
                                            ->dehydrated(),
                                        TextInput::make('shipping_method_name')
                                            ->label('Ekspedisi')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->hidden(fn(Get $get, string $operation) => $operation !== 'create' || !filled($get('purchase_order_id'))),

                                        TextInput::make('tracking_number')
                                            ->label('No. Resi'),
                                    ]),
                            ]),

                        Section::make('Item Tagihan')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('barcode_scanner')
                                            ->label('Scan Barcode/SKU')
                                            ->placeholder('Scan Barcode/SKU...')
                                            ->live()
                                            ->disabled(fn(Get $get, string $operation) => $operation === 'create' && filled($get('purchase_order_id')))
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (blank($state))
                                                    return;

                                                $state = trim($state);
                                                $product = \App\Models\Product::where('sku', $state)->first();

                                                if ($product) {
                                                    $items = $get('items') ?? [];
                                                    $existingIndex = null;

                                                    foreach ($items as $index => $item) {
                                                        if (isset($item['product_id']) && $item['product_id'] == $product->id) {
                                                            $existingIndex = $index;
                                                            break;
                                                        }
                                                    }

                                                    $price = $product->cost_price ?? $product->buy_price ?? $product->price ?? 0;

                                                    if ($existingIndex !== null) {
                                                        $items[$existingIndex]['quantity'] = ($items[$existingIndex]['quantity'] ?? 0) + 1;
                                                        $qty = (float) $items[$existingIndex]['quantity'];
                                                        $uPrice = (float) ($items[$existingIndex]['unit_price'] ?? $price);
                                                        $items[$existingIndex]['total_price'] = $qty * $uPrice;
                                                    } else {
                                                        $items[] = [
                                                            'product_id' => $product->id,
                                                            'product_name' => $product->name,
                                                            'description' => $product->description,
                                                            'quantity' => 1,
                                                            'unit_id' => $product->unit_id,
                                                            'unit_name' => $product->unit?->name ?? '',
                                                            'unit_price' => $price,
                                                            'discount_percent' => 0,
                                                            'tax_name' => 'Bebas Pajak',
                                                            'total_price' => $price,
                                                        ];
                                                    }

                                                    $set('items', $items);
                                                    $set('barcode_scanner', null);
                                                    self::updateTotals($get, $set);
                                                }
                                            }),
                                        Toggle::make('tax_inclusive')
                                            ->label('Harga termasuk pajak')
                                            ->inline(false)
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set)),
                                    ]),

                                Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Select::make('product_id_select')
                                            ->label('Produk')
                                            ->relationship('product', 'name', modifyQueryUsing: fn($query) => $query->active())
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpan(3)
                                            ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('../../purchase_order_id')))
                                            ->dehydrated(false)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                $set('product_id', $state);
                                                if ($product = \App\Models\Product::with('unit')->find($state)) {
                                                    $set('product_name', $product->name);
                                                    $set('description', $product->description);
                                                    $set('unit_id', $product->unit_id);
                                                    $set('unit_name', $product->unit?->name ?? '');
                                                    $set('unit_price', $product->cost_price ?? $product->buy_price ?? $product->price ?? 0);
                                                }
                                            }),
                                        TextInput::make('product_name')
                                            ->label('Produk')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpan(3)
                                            ->hidden(fn(Get $get, string $operation) => $operation !== 'create' || !filled($get('../../purchase_order_id'))),
                                        Hidden::make('product_id')
                                            ->dehydrated(),

                                        TextInput::make('description')
                                            ->label('Deskripsi')
                                            ->columnSpan(2),
                                        TextInput::make('quantity')
                                            ->label('Kuantitas')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->live(onBlur: true)
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
                                            })
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component)),

                                        Select::make('unit_id_select')
                                            ->label('Satuan')
                                            ->placeholder('Pilih')
                                            ->relationship('unit', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->columnSpan(2)
                                            ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('../../purchase_order_id')))
                                            ->dehydrated(false)->live()->afterStateUpdated(fn($state, Set $set) => $set('unit_id', $state)),
                                        TextInput::make('unit_name')
                                            ->label('Satuan')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpan(2)
                                            ->hidden(fn(Get $get, string $operation) => $operation !== 'create' || !filled($get('../../purchase_order_id'))),
                                        Hidden::make('unit_id')
                                            ->dehydrated(),

                                        TextInput::make('discount_percent')
                                            ->label('Diskon (%)')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component)),
                                        TextInput::make('unit_price')
                                            ->label('Harga')
                                            ->numeric()
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component)),
                                        TextInput::make('tax_name')
                                            ->label('Pajak')
                                            ->disabled()
                                            ->dehydrated(),
                                        TextInput::make('total_price')
                                            ->label('Total')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated(),
                                    ])
                                    ->columns(12)
                                    ->columnSpanFull()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                    ->addActionLabel('Tambah Item'),
                            ])->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Group::make()
                                    ->schema([
                                        Textarea::make('notes')
                                            ->label('Pesan'),
                                        FileUpload::make('attachments')
                                            ->label('Lampiran')
                                            ->multiple(),
                                    ])->columnSpan(1),

                                Group::make()
                                    ->schema([
                                        TextInput::make('sub_total')
                                            ->label('Sub Total')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->prefix('Rp'),
                                        TextInput::make('discount_amount')
                                            ->label('Diskon')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp'),
                                        TextInput::make('tax_amount')
                                            ->label('Pajak')
                                            ->numeric()
                                            ->readOnly()
                                            ->prefix('Rp'),
                                        TextInput::make('shipping_cost')
                                            ->label('Biaya Pengiriman')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp'),
                                        TextInput::make('other_cost')
                                            ->label('Biaya Transaksi')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp'),
                                        TextInput::make('total_amount')
                                            ->label('Total')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->prefix('Rp'),
                                        TextInput::make('down_payment')
                                            ->label('Uang Muka')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp'),
                                        TextInput::make('balance_due')
                                            ->label('Sisa Tagihan')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->prefix('Rp'),
                                    ])->columns(1),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function calculateLineTotal(Get $get, Set $set, $component = null): void
    {
        $qty = (float) $get('quantity');
        $price = (float) $get('unit_price');
        $discountPercent = (float) $get('discount_percent');

        $subtotal = $qty * $price;
        $discountAmount = $subtotal * ($discountPercent / 100);
        $lineTotal = $subtotal - $discountAmount;

        $set('total_price', $lineTotal);
        self::updateTotals($get, $set);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];
        $subTotal = 0;
        foreach ($items as $item) {
            $subTotal += (float) ($item['total_price'] ?? 0);
        }

        $discountTotal = (float) ($get('discount_amount') ?? 0);
        $shippingCost = (float) ($get('shipping_cost') ?? 0);
        $otherCost = (float) ($get('other_cost') ?? 0);
        $downPayment = (float) ($get('down_payment') ?? 0);

        $totalAmount = $subTotal - $discountTotal + $shippingCost + $otherCost;
        $balanceDue = $totalAmount - $downPayment;

        $set('sub_total', $subTotal);
        $set('total_amount', $totalAmount);
        $set('balance_due', $balanceDue);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Vendor')
                    ->sortable()
                    ->searchable()
                    ->description(fn($record) => $record->supplier?->company_name ?? null),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Referensi')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('purchaseOrder.number')
                    ->label('Nomor PO')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tgl.')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Tgl. Jt. Tempo')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_date')
                    ->label('Tgl. Pengiriman')
                    ->getStateUsing(function ($record) {
                        $delivery = $record->deliveries()->latest('date')->first();
                        return $delivery?->date;
                    })
                    ->date('d/m/Y')
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderBy('shipping_date', $direction))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tgl. Pelunasan')
                    ->getStateUsing(function ($record) {
                        if ($record->status !== 'paid' && $record->payment_status !== 'paid')
                            return null;
                        $debt = \App\Models\Debt::where('reference', $record->number)->first();
                        if ($debt) {
                            $lastPayment = $debt->payments()->latest('date')->first();
                            return $lastPayment?->date;
                        }
                        return $record->date;
                    })
                    ->date('d/m/Y')
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderBy('date', $direction))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('due_days')
                    ->label('Jatuh Tempo')
                    ->getStateUsing(function ($record) {
                        if (!$record->due_date)
                            return '-';
                        if ($record->status === 'paid' || $record->payment_status === 'paid') {
                            return '0 Hari';
                        }
                        $now = now()->startOfDay();
                        $due = \Illuminate\Support\Carbon::parse($record->due_date)->startOfDay();
                        $diff = $now->diffInDays($due, false);
                        if ($diff < 0) {
                            return abs($diff) . ' Hari lalu';
                        }
                        return $diff . ' Hari';
                    })
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderBy('due_date', $direction)),
                Tables\Columns\TextColumn::make('paymentTerm.name')
                    ->label('Termin')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('tags.name')
                    ->label('Tag')
                    ->badge()
                    ->separator(',')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'posted' => 'Terbit',
                        'paid' => 'Lunas',
                        'partial' => 'Dibayar Sebagian',
                        'void' => 'Batal',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'posted' => 'warning',
                        'paid' => 'success',
                        'partial' => 'info',
                        'void' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Sisa Tagihan')
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(fn($state) => number_format($state ?? 0, 0, ',', '.')),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(fn($state) => number_format($state ?? 0, 0, ',', '.')),
            ])
            ->defaultSort('date', 'desc')
            ->actions([
                ActionGroup::make([ViewAction::make(), EditAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseInvoices::route('/'),
            'create' => Pages\CreatePurchaseInvoice::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditPurchaseInvoice::route('/{record}/edit'),
        ];
    }
}
