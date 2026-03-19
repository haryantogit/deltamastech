<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
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
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\ActionGroup as TableActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_hub_pembelian');
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|\UnitEnum|null $navigationGroup = 'Pembelian';
    protected static ?int $navigationSort = 10;
    protected static string|null $navigationLabel = 'Pesanan Pembelian';
    protected static ?string $pluralModelLabel = 'Pesanan Pembelian';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Informasi Utama')
                            ->schema([
                                Select::make('supplier_id')
                                    ->relationship('supplier', 'name', fn($query) => $query->whereIn('type', ['vendor', 'both']))
                                    ->required()
                                    ->label('Vendor')
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('number')
                                    ->required()
                                    ->label('Nomor')
                                    ->readOnly()
                                    ->default(fn() => \App\Models\NumberingSetting::getNextNumber('purchase_order') ?? 'PO/' . date('Ymd') . '-' . rand(100, 999)),
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
                                    ->default(1),
                                TextInput::make('reference')
                                    ->label('Referensi'),
                                Select::make('tags')
                                    ->relationship('tags', 'name')
                                    ->multiple()
                                    ->label('Tag')
                                    ->preload(),
                            ])->columns(['md' => 2]),

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

                                                    if ($product->is_fixed_asset) {
                                                        $price = $product->purchase_price ?? 0;
                                                    } else {
                                                        $price = $product->cost_price ?? $product->buy_price ?? $product->price ?? 0;
                                                    }

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

                                                    // Delay updateTotals to ensure state is committed
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
                                            ->columnSpan(['lg' => 2]),

                                        Toggle::make('tax_inclusive')
                                            ->label('Harga termasuk pajak')
                                            ->inline(false)
                                            ->default(false)
                                            ->reactive()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->extraAttributes(['class' => 'mt-8'])
                                            ->columnSpan(['lg' => 1]),
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
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, Set $set, Get $get, $component) {
                                                                if ($product = \App\Models\Product::find($state)) {
                                                                    $price = $product->cost_price ?? $product->buy_price ?? $product->price ?? 0;
                                                                    $set('unit_price', number_format((float)$price, 0, ',', '.'));
                                                                    $set('description', $product->description);
                                                                    $set('unit_id', $product->unit_id);
 
                                                                    // Auto-populate tax
                                                                    $taxName = 'Bebas Pajak';
                                                                    if ($product->purchase_tax_id) {
                                                                        if (is_numeric($product->purchase_tax_id)) {
                                                                            $tax = \App\Models\Tax::find($product->purchase_tax_id);
                                                                            $taxName = $tax ? $tax->name : 'Bebas Pajak';
                                                                        } else {
                                                                            $taxName = $product->purchase_tax_id;
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
                                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component))
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
                                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"])
                                                            ->readOnly()
                                                            ->dehydrated()
                                                            ->columnSpan(['default' => 1, 'lg' => 3]),
                                                    ]),
                                            ])
                                            ->columnSpanFull()
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->addActionLabel('Tambah Item'),
                                    ])->extraAttributes(['class' => 'w-full overflow-x-auto overflow-y-visible border rounded-xl bg-gray-50/50 dark:bg-white/5']),
                            ])->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Group::make()
                                    ->schema([
                                        Textarea::make('notes')
                                            ->label('Pesan')
                                            ->rows(3),
                                        FileUpload::make('attachments')
                                            ->label('Lampiran')
                                            ->multiple(),
                                    ])->columnSpan(['lg' => 1]),

                                Group::make()
                                    ->schema([
                                        TextInput::make('sub_total')
                                            ->label('Sub Total')
                                            ->readOnly()
                                            ->default(0)
                                            ->placeholder('0')
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"]),
 


                                        Toggle::make('has_discount')
                                            ->label('Tambahan Diskon')
                                            ->inline()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                if (!$state) {
                                                    $set('discount_amount', 0);
                                                }
                                                self::updateTotals($get, $set);
                                            }),
                                        TextInput::make('discount_amount')
                                            ->label(null)
                                            ->hidden(fn(Get $get) => !$get('has_discount'))
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->placeholder('0')
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"]),

                                        Toggle::make('has_shipping')
                                            ->label('Biaya Pengiriman')
                                            ->inline()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                if (!$state) {
                                                    $set('shipping_cost', 0);
                                                }
                                                self::updateTotals($get, $set);
                                            }),
                                        TextInput::make('shipping_cost')
                                            ->label(null)
                                            ->hidden(fn(Get $get) => !$get('has_shipping'))
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->placeholder('0')
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"]),

                                        Toggle::make('has_other_cost')
                                            ->label('Biaya Lainnya')
                                            ->inline()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                if (!$state) {
                                                    $set('other_cost', 0);
                                                }
                                                self::updateTotals($get, $set);
                                            }),
                                        TextInput::make('other_cost')
                                            ->label(null)
                                            ->hidden(fn(Get $get) => !$get('has_other_cost'))
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->placeholder('0')
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"]),

                                        TextInput::make('total_amount')
                                            ->label('Total')
                                            ->readOnly()
                                            ->default(0)
                                            ->placeholder('0')
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"]),

                                        Toggle::make('has_down_payment')
                                            ->label('Uang Muka (DP)')
                                            ->inline()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                if (!$state) {
                                                    $set('down_payment', 0);
                                                }
                                                self::updateTotals($get, $set);
                                            }),
                                        TextInput::make('down_payment')
                                            ->label('Nominal DP')
                                            ->hidden(fn(Get $get) => !$get('has_down_payment'))
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->placeholder('0')
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"]),

                                        TextInput::make('balance_due')
                                            ->label('Sisa Tagihan')
                                            ->readOnly()
                                            ->placeholder('0')
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"])
                                            ->dehydrated(false), // Not saved to DB, just for display
                                    ])->columnSpan(['lg' => 1]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function calculateLineTotal(Get $get, Set $set, $component = null, array $inputOverrides = []): void
    {
        $parseNumber = function($val) {
            if (empty($val)) return 0;
            $val = (string) $val;
            // Handle standard float strings from DB/System (e.g. 1000.00)
            if (preg_match('/^\d+\.\d+$/', $val) && strpos($val, ',') === false && substr_count($val, '.') === 1) {
                return (float) $val;
            }
            // Handle Indonesian format (e.g. 1.000 or 1.000,00)
            return (float) str_replace(['.', ','], ['', '.'], $val);
        };

        $qty = $parseNumber($inputOverrides['quantity'] ?? $get('quantity'));
        $price = $parseNumber($inputOverrides['unit_price'] ?? $get('unit_price'));
        $discountPercent = $parseNumber($inputOverrides['discount_percent'] ?? $get('discount_percent'));
        $taxName = $inputOverrides['tax_name'] ?? $get('tax_name');

        // Robustly find tax_inclusive
        $taxInclusive = (bool) ($get('tax_inclusive') ?? $get('../../tax_inclusive') ?? $get('../../../tax_inclusive') ?? true);

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
        $items = $get('items') ?? $get('../../items') ?? $get('../../../items') ?? [];
        $prefix = '';

        if (!is_array($get('items'))) {
            if (is_array($get('../../items'))) {
                $prefix = '../../';
            } elseif (is_array($get('../../../items'))) {
                $prefix = '../../../';
            }
        }

        $taxInclusive = (bool) ($get($prefix . 'tax_inclusive') ?? true);
        $taxes = \App\Models\Tax::pluck('rate', 'id')->toArray();

        $subTotal = 0;
        $totalTaxAmount = 0;
        $keysProcessed = [];

        foreach ($items as $key => $item) {
            $keysProcessed[] = (string) $key;

            if (isset($overrides['key']) && (string) $key === (string) $overrides['key']) {
                $qty = (float) $overrides['quantity'];
                $price = (float) $overrides['unit_price'];
                $discountPercent = (float) $overrides['discount_percent'];
                $taxId = $overrides['tax_id'] ?? null;
            } else {
                $qty = (float) ($item['quantity'] ?? 0);
                $price = (float) ($item['unit_price'] ?? 0);
                $discountPercent = (float) ($item['discount_percent'] ?? 0);
                $taxId = $item['tax_id'] ?? null;
            }

            $taxRate = (isset($taxes[$taxId])) ? ($taxes[$taxId] / 100) : 0;

            $base = $qty * $price;
            $discounted = $base * (1 - ($discountPercent / 100));

            if ($taxInclusive) {
                $itemTax = $discounted - ($discounted / (1 + $taxRate));
                $subTotal += ($discounted / (1 + $taxRate));
            } else {
                $itemTax = $discounted * $taxRate;
                $subTotal += $discounted;
            }

            $totalTaxAmount += $itemTax;
        }

        if (isset($overrides['key']) && !in_array((string) $overrides['key'], $keysProcessed)) {
            $qty = (float) $overrides['quantity'];
            $price = (float) $overrides['unit_price'];
            $discountPercent = (float) $overrides['discount_percent'];
            $taxId = $overrides['tax_id'] ?? null;

            $taxRate = (isset($taxes[$taxId])) ? ($taxes[$taxId] / 100) : 0;

            $base = $qty * $price;
            $discounted = $base * (1 - ($discountPercent / 100));

            if ($taxInclusive) {
                $itemTax = $discounted - ($discounted / (1 + $taxRate));
                $subTotal += ($discounted / (1 + $taxRate));
            } else {
                $itemTax = $discounted * $taxRate;
                $subTotal += $discounted;
            }

            $totalTaxAmount += $itemTax;
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

        $discountAmount = $parseNumber($get($prefix . 'discount_amount') ?? 0);
        $shippingCost = $parseNumber($get($prefix . 'shipping_cost') ?? 0);
        $otherCost = $parseNumber($get($prefix . 'other_cost') ?? 0);
        $totalAmount = $parseNumber($get($prefix . 'total_amount') ?? 0);
        $downPayment = $parseNumber($get($prefix . 'down_payment') ?? 0);
        $balanceDue = $parseNumber($get($prefix . 'balance_due') ?? 0);

        if ($subTotal > 0 && $discountAmount > 0) {
            $totalTaxAmount = $totalTaxAmount * (($subTotal - $discountAmount) / $subTotal);
        }

        $set($prefix . 'tax_amount', number_format($totalTaxAmount, 0, ',', '.'));

        $grandTotal = ($subTotal - $discountAmount) + $totalTaxAmount + $shippingCost + $otherCost;
        $balanceDue = $grandTotal - $downPayment;

        $set($prefix . 'total_amount', number_format($grandTotal, 0, ',', '.'));
        $set($prefix . 'balance_due', number_format($balanceDue, 0, ',', '.'));
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3])
                            ->schema([
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
                                ])->columnSpan(['default' => 'full', 'md' => 1]),
 
                                Group::make([
                                    TextEntry::make('number')->label('NOMOR'),
                                    TextEntry::make('due_date')->label('TGL JATUH TEMPO')->date('d/m/Y'),
                                    TextEntry::make('reference')->label('REFERENSI')->default(fn($record) => $record->number),
                                ])->columnSpan(['default' => 'full', 'md' => 1]),
 
                                Group::make([
                                    TextEntry::make('date')->label('TGL TRANSAKSI')->date('d/m/Y'),
                                    TextEntry::make('warehouse.name')->label('GUDANG')->default('Unassigned')->color('primary'),
                                    TextEntry::make('tags.name')
                                        ->label('TAG')
                                        ->badge()
                                        ->separator(','),
                                ])->columnSpan(['default' => 'full', 'md' => 1]),
                            ]),
 
                        Section::make()
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->label('')
                                    ->schema([
                                        Grid::make(['default' => 1, 'md' => 9])
                                            ->schema([
                                                TextEntry::make('product.name')
                                                    ->label('PRODUK')
                                                    ->formatStateUsing(fn($record) => $record->product?->sku . ' - ' . $record->product?->name)
                                                    ->color('primary')
                                                    ->columnSpan(['default' => 'full', 'md' => 2]),
                                                TextEntry::make('description')->label('DESKRIPSI')->default('-')->columnSpan(['default' => 'full', 'md' => 1]),
                                                TextEntry::make('quantity')->label('KUANTITAS')->alignRight()->columnSpan(['default' => 'full', 'md' => 1]),
                                                TextEntry::make('unit.name')->label('SATUAN')->columnSpan(['default' => 'full', 'md' => 1]),
                                                TextEntry::make('discount_percent')
                                                    ->label('DISKON')
                                                    ->formatStateUsing(fn($state) => number_format($state, 2) . '%')
                                                    ->alignRight()
                                                    ->columnSpan(['default' => 'full', 'md' => 1]),
                                                TextEntry::make('unit_price')->label('HARGA')->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.'))->alignRight()->columnSpan(['default' => 'full', 'md' => 1]),
                                                TextEntry::make('tax.name')->label('PAJAK')->default('-')->alignRight()->columnSpan(['default' => 'full', 'md' => 1]),
                                                TextEntry::make('total_price')
                                                    ->label('TOTAL')
                                                    ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.'))
                                                    ->alignRight()
                                                    ->weight('bold')
                                                    ->columnSpan(['default' => 'full', 'md' => 1]),
                                            ]),
                                    ]),
 
                                Grid::make(['default' => 1, 'md' => 9])
                                    ->schema([
                                        TextEntry::make('total_quantity')
                                            ->label('Total Kuantitas')
                                            ->state(fn($record) => $record->items->sum('quantity'))
                                            ->weight('bold')
                                            ->alignRight()
                                            ->columnStart(['md' => 4]),
                                    ]),
                            ])
                            ->compact(),
 
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                Group::make([
                                    TextEntry::make('notes')->label('Pesan')->default('-'),
                                    TextEntry::make('attachments')
                                        ->label('Lampiran')
                                        ->html()
                                        ->formatStateUsing(function ($state) {
                                            if (empty($state))
                                                return '-';
                                            return collect($state)->map(function ($path) {
                                                $url = \Illuminate\Support\Facades\Storage::url($path);
                                                $name = basename($path);
                                                return "<a href='{$url}' target='_blank' class='text-primary-600 hover:underline'>{$name}</a>";
                                            })->join('<br>');
                                        }),
                                ])->columnSpan(['default' => 'full', 'md' => 1]),
                                Group::make([
                                    Grid::make(['default' => 1, 'sm' => 2])
                                        ->schema([
                                            TextEntry::make('sub_total')
                                                ->label('Sub total')
                                                ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.'))
                                                ->alignRight(),
                                            TextEntry::make('spacer1')->label('')->state('')->hidden(),
 
                                            TextEntry::make('tax_amount')
                                                ->label('PPN')
                                                ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.'))
                                                ->alignRight(),
                                            TextEntry::make('spacer2')->label('')->state('')->hidden(),
 
                                            TextEntry::make('discount_amount')
                                                ->label('Diskon')
                                                ->color('danger')
                                                ->formatStateUsing(fn($state) => "- " . number_format($state, 0, ',', '.'))
                                                ->alignRight()
                                                ->visible(fn($record) => $record->discount_amount > 0),
                                            TextEntry::make('spacer3')->label('')->state('')->hidden()
                                                ->visible(fn($record) => $record->discount_amount > 0),
 
                                            TextEntry::make('shipping_cost')
                                                ->label('Biaya pengiriman')
                                                ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.'))
                                                ->alignRight()
                                                ->visible(fn($record) => $record->shipping_cost > 0),
                                            TextEntry::make('spacer4')->label('')->state('')->hidden()
                                                ->visible(fn($record) => $record->shipping_cost > 0),
 
                                            TextEntry::make('total_amount')
                                                ->label('Total')
                                                ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.'))
                                                ->weight('bold')
                                                ->size('lg')
                                                ->alignRight(),
                                            TextEntry::make('spacer5')->label('')->state('')->hidden(),
 
                                            TextEntry::make('balance_due')
                                                ->label('Sisa Tagihan')
                                                ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.'))
                                                ->weight('bold')
                                                ->size('lg')
                                                ->color('primary')
                                                ->alignRight(),
                                            TextEntry::make('spacer6')->label('')->state('')->hidden(),
                                        ])
                                        ->columns(['default' => 1, 'sm' => 2]),
                                ])->columnSpan(['default' => 'full', 'md' => 1]),
                            ]),
                    ]),
 
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
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['supplier', 'warehouse']))
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn(PurchaseOrder $record) => PurchaseOrderResource::getUrl('view', ['record' => $record])),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Vendor')
                    ->description(fn(PurchaseOrder $record) => $record->supplier?->company)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Referensi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Gudang')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Tgl. Jatuh Tempo')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentTerm.name')
                    ->label('Termin')
                    ->default('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tags.name')
                    ->label('Tag')
                    ->badge()
                    ->separator(',')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'approved' => 'Dipesan',
                        'ordered' => 'Dipesan',
                        'partial_received' => 'Diterima Sebagian',
                        'received' => 'Diterima',
                        'billed' => 'Tagihan Diterima',
                        'paid' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        'void' => 'Void',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'approved' => 'warning',
                        'ordered' => 'warning',
                        'partial_received' => 'warning',
                        'received' => 'success',
                        'billed' => 'danger',
                        'paid' => 'success',
                        'cancelled', 'void' => 'danger',
                        default => 'gray',
                    })
                    ->label('Status'),
                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Sisa Tagihan')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.'))
                    ->sortable()
                    ->alignRight()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.'))
                    ->sortable()
                    ->alignRight()
                    ->weight('bold'),
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
            ->defaultSort('date', 'desc')
            ->actions([
                TableActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
