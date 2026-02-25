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
                                        Group::make([
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
                                        ])->columnSpan(1),

                                        TextInput::make('number')
                                            ->label('Nomor')
                                            ->required()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->default(fn() => \App\Models\NumberingSetting::getNextNumber('purchase_invoice') ?? 'PI/' . date('Ymd') . '-' . rand(100, 999))
                                            ->columnSpan(1),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        DatePicker::make('date')
                                            ->label('Tgl. Transaksi')
                                            ->required()
                                            ->default(now()),
                                        DatePicker::make('due_date')
                                            ->label('Tgl. Jatuh Tempo')
                                            ->default(now()->addDays(30)),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        Group::make([
                                            Select::make('warehouse_id')
                                                ->relationship('warehouse', 'name')
                                                ->label('Gudang')
                                                ->searchable()
                                                ->preload()
                                                ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('purchase_order_id')))
                                                ->live()
                                                ->default(1),
                                            TextInput::make('warehouse_name')
                                                ->label('Gudang')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->hidden(fn(Get $get, string $operation) => $operation !== 'create' || !filled($get('purchase_order_id'))),
                                        ])->columnSpan(1),

                                        Group::make([
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
                                        ])->columnSpan(1),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        Select::make('payment_term_id')
                                            ->relationship('paymentTerm', 'name')
                                            ->label('Termin')
                                            ->createOptionForm([
                                                TextInput::make('name')->required(),
                                                TextInput::make('days')->numeric()->required(),
                                            ]),
                                        TextInput::make('reference')
                                            ->label('Referensi'),
                                        Select::make('tags')
                                            ->relationship('tags', 'name')
                                            ->multiple()
                                            ->label('Tag')
                                            ->preload(),
                                    ]),
                                Hidden::make('purchase_order_id'),
                                Hidden::make('supplier_id'),
                                Hidden::make('shipping_method_id'),
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
                                            ->createOptionForm([
                                                TextInput::make('name')
                                                    ->label('Nama Ekspedisi')
                                                    ->required(),
                                            ])
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
                                Grid::make(3)
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
                                            })
                                            ->columnSpan(2),
                                        Toggle::make('tax_inclusive')
                                            ->label('Harga termasuk pajak')
                                            ->inline(false)
                                            ->default(false)
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->extraAttributes(['class' => 'mt-8'])
                                            ->columnSpan(1),
                                    ]),

                                Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Select::make('product_id_select')
                                            ->label('Produk')
                                            ->relationship('product', 'name', modifyQueryUsing: function ($query, Get $get) {
                                                $query->active();
                                                $warehouseId = $get('../../warehouse_id');
                                                if ($warehouseId) {
                                                    $query->whereHas('stocks', fn($q) => $q->where('warehouse_id', $warehouseId));
                                                }
                                            })
                                            ->getOptionLabelFromRecordUsing(function ($record, Get $get) {
                                                $warehouseId = $get('../../warehouse_id');
                                                $stock = 0;
                                                if ($warehouseId) {
                                                    $stock = $record->stocks()->where('warehouse_id', $warehouseId)->value('quantity') ?? 0;
                                                }
                                                $stock = (float) $stock;
                                                return "<div class='flex justify-between items-center w-full'><span>{$record->name}</span> <span class='text-xs font-medium px-2 py-0.5 rounded bg-primary-50 text-primary-700 dark:bg-primary-400/10 dark:text-primary-400'>Stok: " . number_format($stock) . "</span></div>";
                                            })
                                            ->allowHtml()
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->disabled(fn(Get $get) => filled($get('../../purchase_order_id')))
                                            ->columnSpan(3)
                                            ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('../../purchase_order_id')))
                                            ->dehydrated(false)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get, $component) {
                                                $set('product_id', $state);
                                                if ($product = \App\Models\Product::with('unit')->find($state)) {
                                                    $set('product_name', $product->name);
                                                    $set('description', $product->description);
                                                    $set('unit_id', $product->unit_id);
                                                    $set('unit_id_select', $product->unit_id);
                                                    $set('unit_name', $product->unit?->name ?? '');
                                                    $set('unit_price', $product->cost_price ?? $product->buy_price ?? $product->price ?? 0);
                                                    $set('quantity', 1);

                                                    // Auto-populate tax
                                                    $taxName = null;
                                                    if ($product->purchase_tax_id) {
                                                        if (is_numeric($product->purchase_tax_id)) {
                                                            $tax = \App\Models\Tax::find($product->purchase_tax_id);
                                                            $taxName = $tax ? $tax->name : null;
                                                        } else {
                                                            $taxName = $product->purchase_tax_id;
                                                        }
                                                    }
                                                    $set('tax_name', $taxName);

                                                    self::calculateLineTotal($get, $set, $component);
                                                }
                                            }),
                                        TextInput::make('product_name')
                                            ->label('Produk')
                                            ->readOnly(fn(Get $get) => filled($get('../../purchase_order_id')))
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
                                            ->default(1)
                                            ->required()
                                            ->readOnly(fn(Get $get) => filled($get('../../purchase_order_id')))
                                            ->live(onBlur: true)
                                            ->suffixAction(
                                                Action::make('checkStock')
                                                    ->button()
                                                    ->size('sm')
                                                    ->color(function (Get $get, $state) {
                                                        $productId = $get('product_id');
                                                        $warehouseId = $get('../../warehouse_id');
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
                                                        $warehouseId = $get('../../warehouse_id');
                                                        if (!$productId || !$warehouseId)
                                                            return '0';
                                                        $product = \App\Models\Product::find($productId);
                                                        if (!$product || !$product->track_inventory)
                                                            return '0';
                                                        $stock = $product->getStockForWarehouse($warehouseId);
                                                        return number_format($stock);
                                                    })
                                            )
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component))
                                            ->columnSpan(2),

                                        Select::make('unit_id_select')
                                            ->label('Satuan')
                                            ->placeholder('Pilih')
                                            ->relationship('unit', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->disabled(fn(Get $get) => filled($get('../../purchase_order_id')))
                                            ->columnSpan(1)
                                            ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('../../purchase_order_id')))
                                            ->dehydrated(false)->live()->afterStateUpdated(fn($state, Set $set) => $set('unit_id', $state)),
                                        TextInput::make('unit_name')
                                            ->label('Satuan')
                                            ->readOnly(fn(Get $get) => filled($get('../../purchase_order_id')))
                                            ->dehydrated(false)
                                            ->columnSpan(1)
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
                                            ->readOnly()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component)),
                                        Select::make('tax_name')
                                            ->label('Pajak')
                                            ->options(fn() => \App\Models\Tax::pluck('name', 'name')->toArray())
                                            ->placeholder('Pilih')
                                            ->disabled(fn(Get $get) => filled($get('../../purchase_order_id')))
                                            ->dehydrated()
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component)),
                                        TextInput::make('total_price')
                                            ->label('Total')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(),
                                    ])
                                    ->columns(12)
                                    ->columnSpanFull()
                                    ->live()
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

                                        Toggle::make('has_discount')
                                            ->label('Tambahan Diskon')
                                            ->inline()
                                            ->live()
                                            ->dehydrated(false)
                                            ->default(fn($get) => (float) ($get('discount_amount') ?? 0) > 0),
                                        TextInput::make('discount_amount')
                                            ->label('Nominal Diskon')
                                            ->numeric()
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp')
                                            ->hidden(fn(Get $get) => !$get('has_discount')),

                                        TextInput::make('tax_amount')
                                            ->label('Pajak')
                                            ->numeric()
                                            ->readOnly()
                                            ->prefix('Rp'),

                                        Toggle::make('has_shipping')
                                            ->label('Biaya Pengiriman')
                                            ->inline()
                                            ->live()
                                            ->dehydrated(false)
                                            ->default(fn($get) => (float) ($get('shipping_cost') ?? 0) > 0),
                                        TextInput::make('shipping_cost')
                                            ->label('Nominal Pengiriman')
                                            ->numeric()
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp')
                                            ->hidden(fn(Get $get) => !$get('has_shipping')),

                                        Toggle::make('has_other_cost')
                                            ->label('Biaya Lainnya')
                                            ->inline()
                                            ->live()
                                            ->dehydrated(false)
                                            ->default(fn($get) => (float) ($get('other_cost') ?? 0) > 0),
                                        TextInput::make('other_cost')
                                            ->label('Nominal Biaya Lain')
                                            ->numeric()
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp')
                                            ->hidden(fn(Get $get) => !$get('has_other_cost')),

                                        TextInput::make('total_amount')
                                            ->label('Total')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->prefix('Rp')
                                            ->extraAttributes(['class' => 'font-bold text-lg']),

                                        Toggle::make('has_down_payment')
                                            ->label('Uang Muka (DP)')
                                            ->inline()
                                            ->live()
                                            ->dehydrated(false)
                                            ->default(fn($get) => (float) ($get('down_payment') ?? 0) > 0),
                                        TextInput::make('down_payment')
                                            ->label('Nominal Uang Muka')
                                            ->numeric()
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp')
                                            ->hidden(fn(Get $get) => !$get('has_down_payment')),

                                        TextInput::make('balance_due')
                                            ->label('Sisa Tagihan')
                                            ->numeric()
                                            ->readOnly()
                                            ->prefix('Rp'),
                                    ])->columnSpan(1),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function calculateLineTotal(Get $get, Set $set, $component = null): void
    {
        $qty = (float) $get('quantity');
        $price = (float) $get('unit_price');
        $discountPercent = (float) $get('discount_percent');
        $taxName = $get('tax_name');

        $taxInclusive = (bool) $get('../../tax_inclusive');
        $taxRate = 0;
        if ($taxName) {
            $tax = \App\Models\Tax::where('name', $taxName)->first();
            $taxRate = $tax ? ($tax->rate / 100) : 0;
        }

        $base = $qty * $price;
        $discounted = $base * (1 - ($discountPercent / 100));

        if ($taxInclusive) {
            $taxAmount = $discounted - ($discounted / (1 + $taxRate));
            $total = $discounted;
        } else {
            $taxAmount = $discounted * $taxRate;
            $total = $discounted + $taxAmount;
        }

        $set('tax_amount', $taxAmount);
        $set('total_price', $total);

        self::updateTotals($get, $set);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $items = $get('items');
        $prefix = '';

        if (!is_array($items)) {
            $items = $get('../../items');
            if (is_array($items)) {
                $prefix = '../../';
            }
        }

        if (!is_array($items)) {
            $items = $get('../../../items');
            if (is_array($items)) {
                $prefix = '../../../';
            }
        }

        if (!is_array($items)) {
            $items = [];
        }

        $subTotal = 0;
        $totalTax = 0;

        $taxInclusive = (bool) $get($prefix . 'tax_inclusive');
        $taxes = \App\Models\Tax::pluck('rate', 'name')->toArray();

        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $taxName = $item['tax_name'] ?? null;

            $taxRate = (isset($taxes[$taxName])) ? ($taxes[$taxName] / 100) : 0;

            $base = $qty * $price;
            $discounted = $base * (1 - ($discountPercent / 100));

            if ($taxInclusive) {
                $itemTax = $discounted - ($discounted / (1 + $taxRate));
                $subTotal += ($discounted / (1 + $taxRate));
            } else {
                $itemTax = $discounted * $taxRate;
                $subTotal += $discounted;
            }
            $totalTax += $itemTax;
        }

        $set($prefix . 'sub_total', $subTotal);
        $set($prefix . 'tax_amount', $totalTax);

        $discountTotal = (float) ($get($prefix . 'discount_amount') ?? 0);
        $shippingCost = (float) ($get($prefix . 'shipping_cost') ?? 0);
        $otherCost = (float) ($get($prefix . 'other_cost') ?? 0);
        $downPayment = (float) ($get($prefix . 'down_payment') ?? 0);

        $totalAmount = $subTotal + $totalTax - $discountTotal + $shippingCost + $otherCost;
        $balance = $totalAmount - $downPayment;

        $set($prefix . 'total_amount', $totalAmount);
        $set($prefix . 'balance_due', $balance);
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
