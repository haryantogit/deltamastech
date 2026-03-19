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

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_hub_penjualan');
    }

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
                                    ->relationship('customer', 'name', fn($query) => $query->whereIn('type', ['customer', 'both']))
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

                                 Group::make()
                                    ->schema([
                                        Repeater::make('items')
                                            ->relationship()
                                            ->schema([
                                                Grid::make(['default' => 1, 'lg' => 24])
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
                                                            ->getOptionLabelFromRecordUsing(function ($record) {
                                                                $sku = $record->sku ?? '-';
                                                                return "<div class='flex justify-between items-center w-full'><span>{$record->name}</span> <span class='text-xs font-medium px-2 py-0.5 rounded bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'>{$sku}</span></div>";
                                                            })
                                                            ->allowHtml()
                                                            ->label('Produk')
                                                            ->preload()
                                                            ->required()
                                                            ->searchable()
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, Set $set, Get $get, $component) {
                                                                if ($product = \App\Models\Product::find($state)) {
                                                                    $set('unit_price', number_format((float)$product->sell_price, 0, ',', '.'));
                                                                    $set('description', $product->description);
                                                                    $set('unit_id', $product->unit_id);

                                                                    // Auto-populate tax
                                                                    $taxName = 'Bebas Pajak';
                                                                    if ($product->sales_tax_id) {
                                                                        if (is_numeric($product->sales_tax_id)) {
                                                                            $tax = \App\Models\Tax::find($product->sales_tax_id);
                                                                            $taxName = $tax ? $tax->name : 'Bebas Pajak';
                                                                        } else {
                                                                            $taxName = $product->sales_tax_id;
                                                                        }
                                                                    }
                                                                    $set('tax_name', $taxName);

                                                                    self::calculateLineTotal($get, $set, $component);
                                                                }
                                                            })
                                                            ->columnSpan(['default' => 1, 'lg' => 6]),
                                                        Textarea::make('description')
                                                            ->label('Deskripsi')
                                                            ->rows(1)
                                                            ->autosize()
                                                            ->columnSpan(['default' => 1, 'lg' => 3]),

                                                        TextInput::make('quantity')
                                                            ->label('Kuantitas')
                                                            ->default(1)
                                                            ->required()
                                                            ->live(debounce: 500)
                                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component))
                                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"])
                                                            ->suffixAction(function (Get $get, $livewire) {
                                                                $productId = $get('product_id');
                                                                $warehouseId = $get('../../warehouse_id') ?? $livewire->data['warehouse_id'] ?? null;
                                                                if ($productId && $warehouseId) {
                                                                    $stock = \App\Models\Stock::where('product_id', $productId)
                                                                        ->where('warehouse_id', $warehouseId)
                                                                        ->value('quantity') ?? 0;
                                                                    return \Filament\Actions\Action::make('stock')
                                                                        ->label(number_format((float)$stock, 0, ',', '.'))
                                                                        ->color($stock > 0 ? 'success' : 'danger')
                                                                        ->badge()
                                                                        ->disabled();
                                                                }
                                                                return null;
                                                            })
                                                            ->columnSpan(['default' => 1, 'lg' => 3]),
                                                        Select::make('unit_id')
                                                            ->relationship('unit', 'name')
                                                            ->label('Satuan')
                                                            ->placeholder('Pilih')
                                                            ->disabled()
                                                            ->dehydrated()
                                                            ->columnSpan(['default' => 1, 'lg' => 2])
                                                            ->live(),

                                                        TextInput::make('unit_price')
                                                            ->label('Harga')
                                                            ->placeholder('0')
                                                            ->required()
                                                            ->readOnly()
                                                            ->live(debounce: 500)
                                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component))
                                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"])
                                                            ->columnSpan(['default' => 1, 'lg' => 3]),

                                                        TextInput::make('discount_percent')
                                                            ->label('Diskon (%)')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->live(debounce: 500)
                                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component))
                                                            ->columnSpan(['default' => 1, 'lg' => 2]),

                                                        Select::make('tax_name')
                                                            ->label('Pajak')
                                                            ->options(function () {
                                                                $taxes = \App\Models\Tax::pluck('name', 'name')->toArray();
                                                                return ['Bebas Pajak' => '...'] + $taxes;
                                                            })
                                                            ->default('Bebas Pajak')
                                                            ->selectablePlaceholder(false)
                                                            ->live()
                                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component))
                                                            ->columnSpan(['default' => 1, 'lg' => 2]),

                                                        TextInput::make('total_price')
                                                            ->label('Total')
                                                            ->placeholder('0')
                                                            ->readOnly()
                                                            ->dehydrated()
                                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"])
                                                            ->columnSpan(['default' => 1, 'lg' => 3]),
                                                    ]),
                                            ])
                                            ->columnSpanFull()
                                            ->addActionLabel('Tambah Item')
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set)),
                                    ])->extraAttributes(['class' => 'w-full overflow-x-auto overflow-y-visible border rounded-xl bg-gray-50/50 dark:bg-white/5']),
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
                                            ->readOnly()
                                            ->default(0)
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"]),

                                        Toggle::make('has_discount')
                                            ->label('Tambahan Diskon')
                                            ->inline()
                                            ->live()
                                            ->dehydrated(false)
                                            ->default(fn($get) => (float) ($get('discount_amount') ?? 0) > 0),
                                        TextInput::make('discount_amount')
                                            ->label('Nominal Diskon')
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"])
                                            ->hidden(fn(Get $get) => !$get('has_discount')),

                                        Toggle::make('has_shipping')
                                            ->label('Biaya Pengiriman')
                                            ->inline()
                                            ->live()
                                            ->dehydrated(false)
                                            ->default(fn($get) => (float) ($get('shipping_cost') ?? 0) > 0),
                                        TextInput::make('shipping_cost')
                                            ->label('Nominal Pengiriman')
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"])
                                            ->hidden(fn(Get $get) => !$get('has_shipping')),

                                        Toggle::make('has_other_cost')
                                            ->label('Biaya Lainnya')
                                            ->inline()
                                            ->live()
                                            ->dehydrated(false)
                                            ->default(fn($get) => (float) ($get('other_cost') ?? 0) > 0),
                                        TextInput::make('other_cost')
                                            ->label('Nominal Biaya Lain')
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"])
                                            ->hidden(fn(Get $get) => !$get('has_other_cost')),

                                        TextInput::make('total_amount')
                                            ->label('Total')
                                            ->readOnly()
                                            ->default(0)
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes([
                                                'x-mask:dynamic' => "\$money(\$input, ',', '.', 0)",
                                                'class' => 'font-bold text-lg'
                                            ]),

                                        Toggle::make('has_down_payment')
                                            ->label('Uang Muka (DP)')
                                            ->inline()
                                            ->live()
                                            ->dehydrated(false)
                                            ->default(fn($get) => (float) ($get('down_payment') ?? 0) > 0),
                                        TextInput::make('down_payment')
                                            ->label('Nominal Uang Muka')
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"])
                                            ->hidden(fn(Get $get) => !$get('has_down_payment')),

                                        TextInput::make('balance_due')
                                            ->label('Sisa Tagihan')
                                            ->readOnly()
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"]),
                                    ])->columnSpan(1),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function calculateLineTotal(Get $get, Set $set, $component = null, array $inputOverrides = []): void
    {
        $parseNumber = function($val) {
            if (empty($val)) return 0;
            $val = (string) $val;
            if (preg_match('/^\d+\.\d+$/', $val) && strpos($val, ',') === false && substr_count($val, '.') === 1) {
                return (float) $val;
            }
            return (float) str_replace(['.', ','], ['', '.'], $val);
        };

        $qty = $parseNumber($inputOverrides['quantity'] ?? $get('quantity'));
        $price = $parseNumber($inputOverrides['unit_price'] ?? $get('unit_price'));
        $discountPercent = $parseNumber($inputOverrides['discount_percent'] ?? $get('discount_percent'));
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

        $set('tax_amount', number_format($taxAmount, 0, ',', '.'));
        $set('total_price', number_format($total, 0, ',', '.'));

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

        $parseNumber = function($val) {
            if (empty($val)) return 0;
            $val = (string) $val;
            if (preg_match('/^\d+\.\d+$/', $val) && strpos($val, ',') === false && substr_count($val, '.') === 1) {
                return (float) $val;
            }
            return (float) str_replace(['.', ','], ['', '.'], $val);
        };

        $set($prefix . 'sub_total', number_format($subTotal, 0, ',', '.'));
        $set($prefix . 'total_tax', number_format($totalTax, 0, ',', '.'));

        $discountAmount = $parseNumber($get($prefix . 'discount_amount') ?? 0);
        $shippingCost = $parseNumber($get($prefix . 'shipping_cost') ?? 0);
        $otherCost = $parseNumber($get($prefix . 'other_cost') ?? 0);
        $dp = $parseNumber($get($prefix . 'down_payment') ?? 0);

        $grandTotal = $subTotal + $totalTax - $discountAmount + $shippingCost + $otherCost;
        $balance = $grandTotal - $dp;

        $set($prefix . 'total_amount', number_format($grandTotal, 0, ',', '.'));
        $set($prefix . 'balance_due', number_format($balance, 0, ',', '.'));
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
                Tables\Columns\TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('number')
                    ->searchable()
                    ->sortable()
                    ->label('Nomor')
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn($record) => route('filament.admin.resources.sales-orders.view', $record)),
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
                    ->label('Tgl. Jatuh Tempo'),
                Tables\Columns\TextColumn::make('paymentTerm.name')
                    ->sortable()
                    ->label('Termin'),
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
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable()
                    ->money('IDR')
                    ->label('Total')
                    ->weight('bold')
                    ->alignRight(),
            ])
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari')
                            ->default(now()->subMonths(3)),
                        DatePicker::make('until')
                            ->label('Sampai')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Dari ' . \Illuminate\Support\Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Sampai ' . \Illuminate\Support\Carbon::parse($data['until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\DeleteAction::make(),
                    Action::make('createDelivery')
                        ->label('Buat Pengiriman')
                        ->icon('heroicon-o-truck')
                        ->color('warning')
                        ->visible(fn() => auth()->user()->can('penjualan.delivery.add'))
                        ->requiresConfirmation()
                        ->url(fn($record) => SalesDeliveryResource::getUrl('create', ['sales_order_id' => $record->id]))
                        ->hidden(
                            fn($record) =>
                            $record->status === 'draft' ||
                            in_array($record->status, ['completed', 'shipped', 'delivered', 'cancelled']) ||
                            $record->deliveries()->exists()
                        ),
                    Action::make('createInvoice')
                        ->label('Buat Tagihan')
                        ->icon('heroicon-o-document-plus')
                        ->color('success')
                        ->visible(fn() => auth()->user()->can('penjualan.invoice.add'))
                        ->requiresConfirmation()
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
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
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
