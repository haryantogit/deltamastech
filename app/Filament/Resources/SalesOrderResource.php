<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOrderResource\Pages;
use App\Models\SalesOrder;
use App\Filament\Resources\SalesDeliveryResource;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;


class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';
    protected static ?int $navigationSort = 10;
    protected static string|null $navigationLabel = 'Pesanan Penjualan';
    protected static ?string $pluralModelLabel = 'Pesanan Penjualan';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(1)
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Informasi Utama')
                            ->schema([
                                Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->required()
                                    ->label('Pelanggan')
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('number')
                                    ->required()
                                    ->label('Nomor')
                                    ->default(fn() => \App\Models\NumberingSetting::getNextNumber('sales_order') ?? 'SO/' . date('Ymd') . '-' . rand(100, 999))
                                    ->readOnly()
                                    ->dehydrated(),
                                DatePicker::make('date')
                                    ->required()
                                    ->label('Tgl. Transaksi')
                                    ->default(now()),
                                DatePicker::make('due_date')
                                    ->label('Tgl. Jatuh Tempo')
                                    ->default(now()->addDays(30)),
                                Select::make('payment_term_id')
                                    ->relationship('paymentTerm', 'name')
                                    ->label('Termin'),
                                Select::make('warehouse_id')
                                    ->relationship('warehouse', 'name')
                                    ->label('Gudang')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->default(1),
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
                            ])->columns(2)->columnSpanFull(),

                        Section::make('Informasi Pengiriman')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        DatePicker::make('shipping_date')
                                            ->label('Tanggal Pengiriman'),
                                        Select::make('shipping_method_id')
                                            ->relationship('shippingMethod', 'name')
                                            ->label('Ekspedisi')
                                            ->createOptionForm([
                                                TextInput::make('name')->label('Nama Ekspedisi')->required(),
                                            ]),
                                        TextInput::make('tracking_number')
                                            ->label('No. Resi'),
                                    ]),
                            ])
                            ->columnSpanFull(),

                        Section::make('Items')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('barcode_scanner')
                                            ->label('Scan Barcode/SKU')
                                            ->placeholder('Scan Barcode/SKU...')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (blank($state))
                                                    return;

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

                                                    $price = $product->sell_price ?? $product->price ?? 0;

                                                    if ($existingIndex !== null) {
                                                        $items[$existingIndex]['quantity'] = ($items[$existingIndex]['quantity'] ?? 0) + 1;
                                                        $qty = (float) $items[$existingIndex]['quantity'];
                                                        $uPrice = (float) ($items[$existingIndex]['unit_price'] ?? $price);
                                                        $items[$existingIndex]['total_price'] = $qty * $uPrice;
                                                    } else {
                                                        $items[] = [
                                                            'product_id' => $product->id,
                                                            'description' => $product->description,
                                                            'quantity' => 1,
                                                            'unit_id' => $product->unit_id,
                                                            'unit_price' => $price,
                                                            'discount_percent' => 0,
                                                            'tax_name' => 'Bebas Pajak',
                                                            'tax_amount' => 0,
                                                            'total_price' => $price,
                                                        ];
                                                    }

                                                    $set('items', $items);
                                                    $set('barcode_scanner', null);
                                                    self::updateTotals($get, $set);

                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Produk ditambahkan: ' . $product->name)
                                                        ->success()
                                                        ->send();
                                                } else {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Produk tidak ditemukan')
                                                        ->danger()
                                                        ->send();
                                                }
                                            })
                                            ->columnSpan(2),

                                        Toggle::make('tax_inclusive')
                                            ->label('Harga termasuk pajak')
                                            ->inline(false)
                                            ->default(false)
                                            ->reactive()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->extraAttributes(['class' => 'mt-8'])
                                            ->columnSpan(1),
                                    ]),

                                Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Select::make('product_id')
                                            ->relationship('product', 'name', modifyQueryUsing: function (Builder $query, Get $get, $livewire) {
                                                $warehouseId = $get('../../warehouse_id') ?? $livewire->data['warehouse_id'] ?? null;
                                                if ($warehouseId) {
                                                    $query->whereHas('stocks', function ($q) use ($warehouseId) {
                                                        $q->where('warehouse_id', $warehouseId);
                                                    });
                                                }
                                                return $query->active();
                                            })
                                            ->getOptionLabelFromRecordUsing(function ($record, Get $get, $livewire) {
                                                $warehouseId = $get('../../warehouse_id') ?? $livewire->data['warehouse_id'] ?? null;
                                                $stock = 0;
                                                if ($warehouseId) {
                                                    $stock = $record->stocks()->where('warehouse_id', $warehouseId)->value('quantity') ?? 0;
                                                }
                                                $stock = (float) $stock;
                                                return "<div class='flex justify-between items-center w-full'><span>{$record->name}</span> <span class='text-xs font-medium px-2 py-0.5 rounded bg-primary-50 text-primary-700 dark:bg-primary-400/10 dark:text-primary-400'>Stok: {$stock}</span></div>";
                                            })
                                            ->allowHtml()
                                            ->label('Produk')
                                            ->preload()
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get, $component) {
                                                if ($product = \App\Models\Product::find($state)) {
                                                    $set('unit_price', $product->sell_price);
                                                    $set('description', $product->description);
                                                    $set('unit_id', $product->unit_id);

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
                                            })
                                            ->columnSpan(3),
                                        TextInput::make('description')
                                            ->label('Deskripsi')
                                            ->columnSpan(2),
                                        TextInput::make('quantity')
                                            ->label('Kuantitas')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->live(debounce: 500)
                                            ->suffixAction(
                                                \Filament\Actions\Action::make('checkStock')
                                                    ->button()
                                                    ->size('sm')
                                                    ->color(function (Get $get, $state) {
                                                        $productId = $get('product_id');
                                                        $warehouseId = $get('warehouse_id') ?? $get('../warehouse_id') ?? $get('../../warehouse_id') ?? $get('../../../warehouse_id');
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
                                        Select::make('unit_id')
                                            ->relationship('unit', 'name')
                                            ->label('Satuan')
                                            ->placeholder('Pilih')
                                            ->searchable(false)
                                            ->columnSpan(1),
                                        TextInput::make('discount_percent')
                                            ->label('Diskon (%)')
                                            ->numeric()
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component))
                                            ->columnSpan(1),
                                        TextInput::make('unit_price')
                                            ->label('Harga')
                                            ->numeric()
                                            ->required()
                                            ->readOnly()
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component))
                                            ->columnSpan(1),
                                        Select::make('tax_name')
                                            ->label('Pajak')
                                            ->placeholder('Pilih')
                                            ->options(\App\Models\Tax::pluck('name', 'name')->toArray())
                                            ->default(null)
                                            ->nullable()
                                            ->live()
                                            ->searchable(false)
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component))
                                            ->columnSpan(1),
                                        Hidden::make('tax_amount'),
                                        TextInput::make('total_price')
                                            ->label('Total')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->columnSpan(1),
                                    ])
                                    ->columns(12)
                                    ->columnSpanFull()
                                    ->addActionLabel('Tambah Item')
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set)),
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
                                            ->label('Lampiran')
                                            ->multiple()
                                            ->directory('sales-orders')
                                            ->columnSpanFull(),

                                    ])->columnSpan(1),

                                Group::make()
                                    ->schema([
                                        TextInput::make('sub_total')
                                            ->label('Sub Total')
                                            ->numeric()
                                            ->readOnly()
                                            ->default(0)
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
                                            ->default(0)
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

    public static function calculateLineTotal(Get $get, Set $set, $component = null, array $inputOverrides = []): void
    {
        $qty = (float) ($inputOverrides['quantity'] ?? $get('quantity'));
        $price = (float) ($inputOverrides['unit_price'] ?? $get('unit_price'));
        $discountPercent = (float) ($inputOverrides['discount_percent'] ?? $get('discount_percent'));
        $taxName = $inputOverrides['tax_name'] ?? $get('tax_name');

        $taxInclusive = (bool) $get('tax_inclusive');
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

        $overrides = [];
        if ($component) {
            $pathParts = explode('.', $component->getStatePath());
            if (count($pathParts) >= 2) {
                $uuid = $pathParts[count($pathParts) - 2];
                $overrides = [
                    'key' => $uuid,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount_percent' => $discountPercent,
                    'tax_name' => $taxName,
                    'tax_amount' => $taxAmount,
                ];
            }
        }

        self::updateTotals($get, $set, $overrides);
    }

    public static function updateTotals(Get $get, Set $set, array $overrides = []): void
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
        $keysProcessed = [];

        $taxInclusive = (bool) $get($prefix . 'tax_inclusive');
        $taxes = \App\Models\Tax::pluck('rate', 'name')->toArray();

        foreach ($items as $key => $item) {
            $keysProcessed[] = (string) $key;
            if (isset($overrides['key']) && (string) $key === (string) $overrides['key']) {
                $qty = (float) $overrides['quantity'];
                $price = (float) $overrides['unit_price'];
                $discountPercent = (float) $overrides['discount_percent'];
                $taxName = $overrides['tax_name'];
            } else {
                $qty = (float) ($item['quantity'] ?? 0);
                $price = (float) ($item['unit_price'] ?? 0);
                $discountPercent = (float) ($item['discount_percent'] ?? 0);
                $taxName = $item['tax_name'] ?? null;
            }

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

        if (isset($overrides['key']) && !in_array((string) $overrides['key'], $keysProcessed)) {
            $qty = (float) $overrides['quantity'];
            $price = (float) $overrides['unit_price'];
            $discountPercent = (float) $overrides['discount_percent'];
            $taxName = $overrides['tax_name'];

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

        $discountAmount = (float) ($get($prefix . 'discount_amount') ?? 0);
        $shippingCost = (float) ($get($prefix . 'shipping_cost') ?? 0);
        $otherCost = (float) ($get($prefix . 'other_cost') ?? 0);
        $dp = (float) ($get($prefix . 'down_payment') ?? 0);

        $grandTotal = $subTotal + $totalTax - $discountAmount + $shippingCost + $otherCost;
        $balance = $grandTotal - $dp;

        $set($prefix . 'total_amount', $grandTotal);
        $set($prefix . 'balance_due', $balance);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Informasi Pesanan')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('customer.name')
                                    ->label('Pelanggan')
                                    ->columnSpan(2),
                                TextEntry::make('number')
                                    ->label('Nomor Pesanan'),
                                TextEntry::make('date')
                                    ->label('Tanggal')
                                    ->date('d/m/Y'),
                                TextEntry::make('due_date')
                                    ->label('Tgl Jatuh Tempo')
                                    ->date('d/m/Y'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn(string $state): string => match (strtolower($state)) {
                                        'draft' => 'gray',
                                        'confirmed' => 'info',
                                        'shipped' => 'warning',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('warehouse.name')
                                    ->label('Gudang')
                                    ->default('Unassigned'),
                                TextEntry::make('reference')
                                    ->label('Referensi'),
                                TextEntry::make('tags.name')
                                    ->label('Tag')
                                    ->badge()
                                    ->separator(','),
                            ]),
                    ]),

                Section::make('Daftar Produk')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Grid::make(8)
                                    ->schema([
                                        TextEntry::make('product.name')
                                            ->label('Produk')
                                            ->columnSpan(2),
                                        TextEntry::make('description')
                                            ->label('Deskripsi')
                                            ->default('-')
                                            ->columnSpan(1),
                                        TextEntry::make('quantity')
                                            ->label('Jumlah')
                                            ->alignCenter()
                                            ->columnSpan(1),
                                        TextEntry::make('unit.name')
                                            ->label('Satuan')
                                            ->alignCenter()
                                            ->columnSpan(1),
                                        TextEntry::make('discount_percent')
                                            ->label('Discount')
                                            ->suffix('%')
                                            ->alignCenter()
                                            ->default('0%')
                                            ->columnSpan(1),
                                        TextEntry::make('tax_name')
                                            ->label('Pajak')
                                            ->default('-')
                                            ->alignCenter()
                                            ->columnSpan(1),
                                        TextEntry::make('unit_price')
                                            ->label('Harga Satuan')
                                            ->money('IDR')
                                            ->alignRight()
                                            ->columnSpan(1),
                                        TextEntry::make('total_price')
                                            ->label('Total')
                                            ->money('IDR')
                                            ->alignRight()
                                            ->weight('bold')
                                            ->columnSpan(1),
                                    ]),
                            ]),
                    ]),

                Section::make('')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Group::make()
                                    ->schema([
                                        TextEntry::make('notes')
                                            ->label('Catatan')
                                            ->default('-'),
                                    ])
                                    ->columnSpan(1),

                                Group::make()
                                    ->schema([
                                        TextEntry::make('sub_total')
                                            ->label('Sub Total')
                                            ->money('IDR')
                                            ->alignEnd(),
                                        TextEntry::make('discount_amount')
                                            ->label('Diskon')
                                            ->money('IDR')
                                            ->alignEnd()
                                            ->visible(fn($record) => $record->discount_amount > 0),
                                        TextEntry::make('shipping_cost')
                                            ->label('Biaya Pengiriman')
                                            ->money('IDR')
                                            ->alignEnd()
                                            ->visible(fn($record) => $record->shipping_cost > 0),
                                        TextEntry::make('other_cost')
                                            ->label('Biaya Lainnya')
                                            ->money('IDR')
                                            ->alignEnd()
                                            ->visible(fn($record) => $record->other_cost > 0),
                                        TextEntry::make('total_amount')
                                            ->label('Total Akhir')
                                            ->money('IDR')
                                            ->alignEnd()
                                            ->weight('bold')
                                            ->size('lg'),
                                        TextEntry::make('down_payment')
                                            ->label('Uang Muka')
                                            ->money('IDR')
                                            ->alignEnd()
                                            ->visible(fn($record) => $record->down_payment > 0),
                                        TextEntry::make('balance_due')
                                            ->label('Sisa Tagihan')
                                            ->money('IDR')
                                            ->alignEnd()
                                            ->weight('bold')
                                            ->color(fn($state) => $state > 0 ? 'danger' : 'success'),
                                    ])
                                    ->columnSpan(1),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['customer', 'warehouse', 'paymentTerm', 'tags']))
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->searchable()
                    ->sortable()
                    ->label('Nomor')
                    ->color('primary')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->sortable()
                    ->searchable()
                    ->label('Pelanggan'),
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->label('Referensi')
                    ->placeholder('-')
                    ->formatStateUsing(function ($state) {
                        if (is_numeric($state) && strpos(strtoupper((string) $state), 'E') !== false) {
                            return number_format((float) $state, 0, '', '');
                        }
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Gudang')
                    ->sortable()
                    ->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Tanggal'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Tgl. Jatuh Tempo')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('paymentTerm.name')
                    ->sortable()
                    ->label('Termin')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tags.name')
                    ->badge()
                    ->separator(',')
                    ->label('Tag'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match (strtolower($state)) {
                        'draft' => 'Draf',
                        'confirmed' => 'Dikonfirmasi',
                        'shipped' => 'Dikirim',
                        'delivered' => 'Terkirim',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        'selesai' => 'Selesai',
                        'terbit' => 'Selesai',
                        'ordered' => 'Dipesan',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'draft' => 'gray',
                        'confirmed' => 'info',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'selesai' => 'success',
                        'terbit' => 'success',
                        'ordered' => 'warning',
                        'processing' => 'warning',
                        default => 'gray',
                    })
                    ->label('Status'),
                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Sisa Tagihan')
                    ->money('IDR')
                    ->sortable()
                    ->alignRight()
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable()
                    ->money('IDR')
                    ->label('Total')
                    ->weight('bold')
                    ->alignRight(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name', modifyQueryUsing: fn($query) => $query->where('type', 'customer'))
                    ->label('Pelanggan')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draf',
                        'confirmed' => 'Dikonfirmasi',
                        'processing' => 'Processing',
                        'completed' => 'Selesai',
                        'shipped' => 'Dikirim',
                        'delivered' => 'Terkirim',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('createDelivery')
                        ->label('Buat Pengiriman')
                        ->icon('heroicon-o-truck')
                        ->color('success')
                        ->url(fn($record) => SalesDeliveryResource::getUrl('create', ['sales_order_id' => $record->id]))
                        ->hidden(
                            fn($record) =>
                            $record->status === 'draft' ||
                            in_array($record->status, ['completed', 'shipped', 'delivered', 'cancelled']) ||
                            $record->deliveries()->exists()
                        ),
                    Action::make('createInvoice')
                        ->label('Buat Tagihan')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->url(fn($record) => SalesInvoiceResource::getUrl('create', ['sales_order_id' => $record->id]))
                        ->hidden(
                            fn($record) =>
                            in_array($record->status, ['draft', 'ordered']) ||
                            $record->invoices()->exists()
                        ),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
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
            'index' => \App\Filament\Resources\SalesOrderResource\Pages\ListSalesOrders::route('/'),
            'create' => \App\Filament\Resources\SalesOrderResource\Pages\CreateSalesOrder::route('/create'),
            'view' => \App\Filament\Resources\SalesOrderResource\Pages\ViewTransaction::route('/{record}'),
            'edit' => \App\Filament\Resources\SalesOrderResource\Pages\EditSalesOrder::route('/{record}/edit'),
        ];
    }
}
