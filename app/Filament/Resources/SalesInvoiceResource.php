<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesInvoiceResource\Pages;
use App\Models\SalesInvoice;
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
use App\Filament\Resources\SalesOrderResource;

class SalesInvoiceResource extends Resource
{
    protected static ?string $model = SalesInvoice::class;

    protected static string|null $navigationLabel = 'Tagihan Penjualan';
    protected static ?string $pluralModelLabel = 'Tagihan Penjualan';
    protected static bool $shouldRegisterNavigation = false;
    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';
    protected static ?int $navigationSort = 20;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

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
                                            Select::make('contact_id')
                                                ->relationship('contact', 'name', modifyQueryUsing: fn($query) => $query->where('type', 'customer'))
                                                ->label('Pelanggan')
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->getOptionLabelUsing(fn($value) => \App\Models\Contact::find($value)?->name ?? $value)
                                                ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('sales_order_id')))
                                                ->dehydrated(),
                                            TextInput::make('contact_name')
                                                ->label('Pelanggan')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->hidden(fn(Get $get, string $operation) => $operation !== 'create' || !filled($get('sales_order_id'))),
                                        ])->columnSpan(1),

                                        TextInput::make('invoice_number')
                                            ->label('Nomor')
                                            ->required()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->default(fn() => \App\Models\NumberingSetting::getNextNumber('sales_invoice') ?? 'INV/' . date('Ymd') . '-' . rand(100, 999))
                                            ->columnSpan(1),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        DatePicker::make('transaction_date')
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
                                                ->live()
                                                ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('sales_order_id')))
                                                ->dehydrated()
                                                ->default(1),
                                            TextInput::make('warehouse_name')
                                                ->label('Gudang')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->hidden(fn(Get $get, string $operation) => $operation !== 'create' || !filled($get('sales_order_id'))),
                                        ])->columnSpan(1),

                                        Group::make([
                                            Select::make('sales_order_id')
                                                ->relationship('salesOrder', 'number')
                                                ->label('Nomor Pesanan')
                                                ->searchable()
                                                ->preload()
                                                ->live()
                                                ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('sales_order_id')))
                                                ->dehydrated()
                                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                    if (!$state)
                                                        return;
                                                    $so = \App\Models\SalesOrder::with(['customer', 'warehouse', 'shippingMethod', 'items.product', 'items.unit'])->find($state);
                                                    if (!$so)
                                                        return;

                                                    $set('contact_id', $so->customer_id);
                                                    $set('warehouse_id', $so->warehouse_id);
                                                    $set('payment_term_id', $so->payment_term_id);
                                                    $set('reference', $so->reference);
                                                    $set('shipping_method_id', $so->shipping_method_id);
                                                    $set('tracking_number', $so->tracking_number);
                                                    $set('tax_inclusive', (bool) $so->tax_inclusive);
                                                    $set('discount_total', $so->discount_amount);
                                                    $set('shipping_cost', $so->shipping_cost);
                                                    $set('other_cost', $so->other_cost);
                                                    $set('down_payment', $so->down_payment);
                                                    $set('notes', $so->notes);

                                                    $items = $so->items->map(fn($item) => [
                                                        'product_id' => $item->product_id,
                                                        'product_name' => $item->product?->name ?? '-',
                                                        'description' => $item->description,
                                                        'qty' => $item->quantity,
                                                        'unit_id' => $item->unit_id,
                                                        'unit_name' => $item->unit?->name ?? '-',
                                                        'price' => $item->unit_price,
                                                        'discount_percent' => $item->discount_percent,
                                                        'tax_name' => $item->tax_name,
                                                        'subtotal' => $item->total_price,
                                                    ])->toArray();

                                                    $set('items', $items);
                                                    self::updateTotals($get, $set);
                                                }),
                                            TextInput::make('sales_order_number')
                                                ->label('Nomor Pesanan')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->hidden(fn(Get $get, string $operation) => $operation !== 'create' || !filled($get('sales_order_id')))
                                                ->suffixAction(
                                                    fn($state) => $state ? Action::make('view_so')
                                                        ->icon('heroicon-m-arrow-top-right-on-square')
                                                        ->url(fn(Get $get) => $get('sales_order_id') ? SalesOrderResource::getUrl('view', ['record' => $get('sales_order_id')]) : null)
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
                                            ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('sales_order_id')))
                                            ->dehydrated(),
                                        TextInput::make('shipping_method_name')
                                            ->label('Ekspedisi')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->hidden(fn(Get $get, string $operation) => $operation !== 'create' || !filled($get('sales_order_id'))),

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
                                            ->disabled(fn(Get $get, string $operation) => $operation === 'create' && filled($get('sales_order_id')))
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

                                                    $price = $product->sell_price ?? 0;

                                                    if ($existingIndex !== null) {
                                                        $items[$existingIndex]['qty'] = ($items[$existingIndex]['qty'] ?? 0) + 1;
                                                        $qty = (float) $items[$existingIndex]['qty'];
                                                        $uPrice = (float) ($items[$existingIndex]['price'] ?? $price);
                                                        $items[$existingIndex]['subtotal'] = $qty * $uPrice;
                                                    } else {
                                                        $items[] = [
                                                            'product_id' => $product->id,
                                                            'product_name' => $product->name,
                                                            'description' => $product->description,
                                                            'qty' => 1,
                                                            'unit_id' => $product->unit_id,
                                                            'unit_name' => $product->unit?->name ?? '',
                                                            'price' => $price,
                                                            'discount_percent' => 0,
                                                            'tax_name' => 'Bebas Pajak',
                                                            'subtotal' => $price,
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
                                            ->disabled(fn(Get $get) => filled($get('../../sales_order_id')))
                                            ->columnSpan(3)
                                            ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('../../sales_order_id')))
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
                                                    $set('price', $product->sell_price ?? 0);
                                                    $set('qty', 1);

                                                    // Auto-populate tax
                                                    $taxName = null;
                                                    if ($product->sales_tax_id) {
                                                        if (is_numeric($product->sales_tax_id)) {
                                                            $tax = \App\Models\Tax::find($product->sales_tax_id);
                                                            $taxName = $tax ? $tax->name : null;
                                                        } else {
                                                            $taxName = $product->sales_tax_id;
                                                        }
                                                    }
                                                    $set('tax_name', $taxName);

                                                    self::calculateLineTotal($get, $set, $component);
                                                }
                                            }),
                                        TextInput::make('product_name')
                                            ->label('Produk')
                                            ->readOnly(fn(Get $get) => filled($get('../../sales_order_id')))
                                            ->dehydrated(false)
                                            ->columnSpan(3)
                                            ->hidden(fn(Get $get, string $operation) => $operation !== 'create' || !filled($get('../../sales_order_id'))),
                                        Hidden::make('product_id')
                                            ->dehydrated(),

                                        TextInput::make('description')
                                            ->label('Deskripsi')
                                            ->columnSpan(2),
                                        TextInput::make('qty')
                                            ->label('Kuantitas')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->readOnly(fn(Get $get) => filled($get('../../sales_order_id')))
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
                                            ->disabled(fn(Get $get) => filled($get('../../sales_order_id')))
                                            ->columnSpan(1)
                                            ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('../../sales_order_id')))
                                            ->dehydrated(false)->live()->afterStateUpdated(fn($state, Set $set) => $set('unit_id', $state)),
                                        TextInput::make('unit_name')
                                            ->label('Satuan')
                                            ->readOnly(fn(Get $get) => filled($get('../../sales_order_id')))
                                            ->dehydrated(false)
                                            ->columnSpan(1)
                                            ->hidden(fn(Get $get, string $operation) => $operation !== 'create' || !filled($get('../../sales_order_id'))),
                                        Hidden::make('unit_id')
                                            ->dehydrated(),

                                        TextInput::make('discount_percent')
                                            ->label('Diskon (%)')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component)),
                                        TextInput::make('price')
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
                                            ->disabled(fn(Get $get) => filled($get('../../sales_order_id')))
                                            ->dehydrated()
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component)),
                                        TextInput::make('subtotal')
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
                                            ->default(fn($get) => (float) ($get('discount_total') ?? 0) > 0),
                                        TextInput::make('discount_total')
                                            ->label('Nominal Diskon')
                                            ->numeric()
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp')
                                            ->hidden(fn(Get $get) => !$get('has_discount')),

                                        TextInput::make('total_tax')
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
        $qty = (float) $get('qty');
        $price = (float) $get('price');
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
        $set('subtotal', $total);

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
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
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
        $set($prefix . 'total_tax', $totalTax);

        $discountAmount = (float) ($get($prefix . 'discount_total') ?? 0);
        $shippingCost = (float) ($get($prefix . 'shipping_cost') ?? 0);
        $otherCost = (float) ($get($prefix . 'other_cost') ?? 0);
        $dp = (float) ($get($prefix . 'down_payment') ?? 0);

        $totalAmount = $subTotal + $totalTax - $discountAmount + $shippingCost + $otherCost;
        $balance = $totalAmount - $dp;

        $set($prefix . 'total_amount', $totalAmount);
        $set($prefix . 'balance_due', $balance);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('contact.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Ref.')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('salesOrder.number')
                    ->label('PO #')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tgl.')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Tgl. Jt. Tempo')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipping_date')
                    ->label('Tgl. Pengiriman')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('tanggal_pembayaran')
                    ->label('Tgl. Pembayaran')
                    ->getStateUsing(fn(SalesInvoice $record) => $record->receivable?->payments()->latest('date')->first()?->date)
                    ->date('d/m/Y')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('tanggal_pelunasan')
                    ->label('Tgl. Pelunasan')
                    ->getStateUsing(function (SalesInvoice $record) {
                        if ($record->status !== 'paid' && $record->payment_status !== 'paid')
                            return null;
                        return $record->receivable?->payments()->latest('date')->first()?->date;
                    })
                    ->date('d/m/Y')
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
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tags.name')
                    ->badge()
                    ->separator(',')
                    ->label('Tag')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn(string $state, $record): string => match ($state) {
                        'draft' => 'Draft',
                        'posted' => match ($record->payment_status) {
                                'partial' => 'Dibayar Sebagian',
                                'unpaid' => 'Terbit',
                                default => 'Terbit',
                            },
                        'paid' => 'Lunas',
                        'void' => 'Batal',
                        'partial' => 'Dibayar Sebagian',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state, $record): string => match ($state) {
                        'draft' => 'gray',
                        'posted' => match ($record->payment_status) {
                                'partial' => 'info',
                                'unpaid' => 'warning',
                                default => 'warning',
                            },
                        'paid' => 'success',
                        'partial' => 'info',
                        'void' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Sisa')
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(fn($state) => number_format($state ?? 0, 0, ',', '.')),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(fn($state) => number_format($state ?? 0, 0, ',', '.')),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->actions([
                ActionGroup::make([ViewAction::make(), EditAction::make()]),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesInvoices::route('/'),
            'create' => Pages\CreateSalesInvoice::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditSalesInvoice::route('/{record}/edit'),
        ];
    }
}
