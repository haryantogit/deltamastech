<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseQuoteResource\Pages;
use App\Models\PurchaseQuote;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action as TablesAction;
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
use Filament\Forms;

class PurchaseQuoteResource extends Resource
{
    protected static ?string $model = PurchaseQuote::class;

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_hub_pembelian');
    }

    protected static string|null $navigationLabel = 'Penawaran Pembelian';
    protected static ?string $pluralModelLabel = 'Penawaran Pembelian';
    protected static bool $shouldRegisterNavigation = false;
    protected static string|\UnitEnum|null $navigationGroup = 'Pembelian';
    protected static ?int $navigationSort = 41;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

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
                                    ->label('Nomor')
                                    ->required()
                                    ->default(fn() => \App\Models\NumberingSetting::getNextNumber('purchase_quote') ?? 'PQ/' . str_pad((\App\Models\PurchaseQuote::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT))
                                    ->readOnly(),
                                DatePicker::make('date')
                                    ->label('Tgl. Transaksi')
                                    ->required()
                                    ->default(now()),
                                DatePicker::make('due_date')
                                    ->label('Kadaluarsa')
                                    ->default(now()->addDays(30)),
                                Grid::make(3)
                                    ->schema([
                                        Select::make('payment_term_id')
                                            ->relationship('paymentTerm', 'name')
                                            ->label('Termin')
                                            ->preload()
                                            ->searchable(),
                                        // Warehouse relationship commented out if not requested or needed yet, but keeping structure
                                        // Select::make('warehouse_id')
                                        //     ->relationship('warehouse', 'name')
                                        //     ->label('Gudang')
                                        //     ->searchable()
                                        //     ->preload(),
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
                                    ])
                                    ->columnSpanFull(),
                                Hidden::make('status')
                                    ->default('draft'),
                            ])->columns(2),

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
                            ]),

                        Section::make('Items')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('barcode_scanner')
                                            ->label('Scan Barcode/SKU')
                                            ->placeholder('Scan Barcode/SKU...')
                                            ->live()
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
                                            ->columnSpan(['lg' => 2]),

                                        Toggle::make('tax_inclusive')
                                            ->label('Harga termasuk pajak')
                                            ->default(true)
                                            ->reactive()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->inline(false)
                                            ->columnSpan(['lg' => 1])
                                            ->extraAttributes(['class' => 'mt-8']),
                                    ]),

                                 Group::make()
                                    ->schema([
                                        Repeater::make('items')
                                            ->relationship()
                                            ->schema([
                                                Grid::make(24)
                                                    ->schema([
                                                        Select::make('product_id')
                                                            ->label('Produk')
                                                            ->relationship('product', 'name', modifyQueryUsing: function ($query) {
                                                                $query->active();
                                                            })
                                                            ->getOptionLabelFromRecordUsing(function ($record) {
                                                                return "<div>
                                                                            <div class='font-medium'>{$record->name}</div>
                                                                            <div class='text-xs text-gray-500'>{$record->sku}</div>
                                                                        </div>";
                                                            })
                                                            ->getOptionLabelUsing(function ($value) {
                                                                $product = \App\Models\Product::find($value);
                                                                return $product ? "{$product->sku} - {$product->name}" : null;
                                                            })
                                                            ->allowHtml()
                                                            ->searchable()
                                                            ->preload()
                                                            ->required()
                                                            ->columnSpan(['lg' => 6])
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, Set $set, Get $get, $component) {
                                                                if ($product = \App\Models\Product::with('unit')->find($state)) {
                                                                    $set('description', $product->description);
                                                                    $set('unit_id', $product->unit_id);
                                                                    $set('unit_price', number_format($product->cost_price ?? $product->buy_price ?? $product->price ?? 0, 0, ',', '.'));
                                                                    $set('quantity', 1);

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
                                                            }),

                                                        Textarea::make('description')
                                                            ->label('Deskripsi')
                                                            ->rows(1)
                                                            ->autosize()
                                                            ->columnSpan(['lg' => 3]),

                                                        TextInput::make('quantity')
                                                            ->label('Kuantitas')
                                                            ->default(1)
                                                            ->required()
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component))
                                                            ->extraAlpineAttributes([
                                                                'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                                            ])
                                                            ->suffixAction(function (Get $get) {
                                                                $productId = $get('product_id');
                                                                if ($productId) {
                                                                    $stock = \App\Models\Stock::where('product_id', $productId)->sum('quantity') ?? 0;
                                                                    return \Filament\Actions\Action::make('stock')
                                                                        ->label(number_format($stock, 0, ',', '.'))
                                                                        ->color($stock > 0 ? 'success' : 'danger')
                                                                        ->badge()
                                                                        ->disabled();
                                                                }
                                                                return null;
                                                            })
                                                            ->columnSpan(['lg' => 3]),

                                                        Select::make('unit_id')
                                                            ->label('Satuan')
                                                            ->relationship('unit', 'name')
                                                            ->placeholder('Pilih')
                                                            ->disabled()
                                                            ->dehydrated()
                                                            ->searchable()
                                                            ->preload()
                                                            ->columnSpan(['lg' => 2])
                                                            ->live(),

                                                        TextInput::make('unit_price')
                                                            ->label('Harga')
                                                            ->required()
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function ($state, Set $set, Get $get, $component) {
                                                                $cleanValue = str_replace(['.', ','], '', $state);
                                                                if (is_numeric($cleanValue)) {
                                                                    $set('unit_price', number_format((float) $cleanValue, 0, ',', '.'));
                                                                }
                                                                self::calculateLineTotal($get, $set, $component);
                                                            })
                                                            ->extraAlpineAttributes([
                                                                'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                                            ])
                                                            ->columnSpan(['lg' => 3]),

                                                        TextInput::make('discount_percent')
                                                            ->label('Diskon (%)')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component))
                                                            ->columnSpan(['lg' => 2]),

                                                        Select::make('tax_name')
                                                            ->label('Pajak')
                                                            ->options(function () {
                                                                $taxes = \App\Models\Tax::pluck('name', 'name')->toArray();
                                                                return ['Bebas Pajak' => '...'] + $taxes;
                                                            })
                                                            ->getOptionLabelFromRecordUsing(fn($record) => $record->name === 'Bebas Pajak' ? '...' : $record->name)
                                                            ->default('Bebas Pajak')
                                                            ->selectablePlaceholder(false)
                                                            ->dehydrated()
                                                            ->live()
                                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component))
                                                            ->columnSpan(['lg' => 2]),

                                                        TextInput::make('total_price')
                                                            ->label('Total')
                                                            ->readOnly()
                                                            ->dehydrated()
                                                            ->extraAlpineAttributes([
                                                                'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                                            ])
                                                            ->columnSpan(['lg' => 3]),
                                                    ]),
                                            ])
                                            ->columnSpanFull()
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->addActionLabel('Tambah Item'),
                                    ])->extraAttributes(['class' => 'w-full overflow-y-visible border rounded-xl bg-gray-50/50 dark:bg-white/5']),
                            ])->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Group::make()
                                    ->schema([
                                        Textarea::make('notes')->label('Pesan')
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
                                            ->extraAlpineAttributes([
                                                'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                            ]),

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
                                            ->label('Nominal Diskon')
                                            ->hidden(fn(Get $get) => !$get('has_discount'))
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->extraAlpineAttributes([
                                                'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                            ]),

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
                                            ->label('Nominal Pengiriman')
                                            ->hidden(fn(Get $get) => !$get('has_shipping'))
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->extraAlpineAttributes([
                                                'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                            ]),

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
                                            ->label('Nominal Biaya Lain')
                                            ->hidden(fn(Get $get) => !$get('has_other_cost'))
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->extraAlpineAttributes([
                                                'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                            ]),

                                        TextInput::make('total_amount')
                                            ->label('Total')
                                            ->readOnly()
                                            ->default(0)
                                            ->extraAlpineAttributes([
                                                'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                            ])
                                            ->extraAttributes(['class' => 'font-bold text-lg text-primary-600']),
                                    ])->columnSpan(['lg' => 1]),
                            ]),
                    ])->columnSpanFull(),

            ]);
    }

    private static function parseNumber($value): float
    {
        if (is_numeric($value)) return (float) $value;
        if (empty($value)) return 0;

        $cleanValue = str_replace('.', '', $value);
        $cleanValue = str_replace(',', '.', $cleanValue);

        return (float) $cleanValue;
    }

    public static function calculateLineTotal(Get $get, Set $set, $component = null, array $inputOverrides = []): void
    {
        $qty = self::parseNumber($inputOverrides['quantity'] ?? $get('quantity'));
        $price = self::parseNumber($inputOverrides['unit_price'] ?? $get('unit_price'));
        $discountPercent = self::parseNumber($inputOverrides['discount_percent'] ?? $get('discount_percent'));
        $taxId = $inputOverrides['tax_id'] ?? $get('tax_id');

        $taxRate = 0;
        if ($taxId) {
            $tax = \App\Models\Tax::find($taxId);
            $taxRate = $tax ? ($tax->rate / 100) : 0;
        }

        $base = $qty * $price;
        $discounted = $base * (1 - ($discountPercent / 100));

        $taxInclusive = (bool) $get('tax_inclusive');

        if ($taxInclusive) {
            $taxAmount = $discounted - ($discounted / (1 + $taxRate));
            $total = $discounted;
        } else {
            $taxAmount = $discounted * $taxRate;
            $total = $discounted + $taxAmount;
        }

        $set('tax_amount', $taxAmount);
        $set('total_price', number_format($total, 0, ',', '.'));

        // Prepare overrides for updateTotals
        $overrides = [];
        if ($component) {
            $pathParts = explode('.', $component->getStatePath());
            if (count($pathParts) >= 2) {
                // If nested in repeater -> items.uuid.field
                $uuid = $pathParts[count($pathParts) - 2];
                $overrides = [
                    'key' => $uuid,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount_percent' => $discountPercent,
                    'tax_id' => $taxId,
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

        $taxInclusive = (bool) $get($prefix . 'tax_inclusive');
        $subTotal = 0;
        $totalTax = 0;
        $taxes = \App\Models\Tax::pluck('rate', 'id')->all();

        $keysProcessed = [];

        foreach ($items as $key => $item) {
            $keysProcessed[] = (string) $key;

            if (isset($overrides['key']) && (string) $key === (string) $overrides['key']) {
                $qty = (float) $overrides['quantity'];
                $price = (float) $overrides['unit_price'];
                $discountPercent = (float) $overrides['discount_percent'];
                $taxId = $overrides['tax_id'] ?? null;
            } else {
                $qty = self::parseNumber($item['quantity'] ?? 0);
                $price = self::parseNumber($item['unit_price'] ?? 0);
                $discountPercent = self::parseNumber($item['discount_percent'] ?? 0);
                $taxId = $item['tax_id'] ?? null;
            }

            $taxRate = 0;
            if ($taxId && isset($taxes[$taxId])) {
                $taxRate = $taxes[$taxId] / 100;
            }

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
            $taxId = $overrides['tax_id'] ?? null;

            $taxRate = 0;
            if ($taxId && isset($taxes[$taxId])) {
                $taxRate = $taxes[$taxId] / 100;
            }

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

        $set($prefix . 'sub_total', number_format($subTotal, 0, ',', '.'));

        $discountAmount = self::parseNumber($get($prefix . 'discount_amount') ?? 0);
        $shippingCost = self::parseNumber($get($prefix . 'shipping_cost') ?? 0);
        $otherCost = self::parseNumber($get($prefix . 'other_cost') ?? 0);

        $grandTotal = $subTotal + $totalTax - $discountAmount + $shippingCost + $otherCost;

        $set($prefix . 'total_amount', number_format($grandTotal, 0, ',', '.'));
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Informasi Penawaran')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('number')->label('No. Penawaran'),
                                TextEntry::make('date')->label('Tanggal')->date(),
                                TextEntry::make('due_date')->label('Jatuh Tempo')->date(),
                                TextEntry::make('supplier.name')->label('Vendor'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('reference')->label('Referensi'),
                            ]),
                    ]),

                Section::make('Item Barang / Jasa')->schema([
                    RepeatableEntry::make('items')
                        ->schema([
                            Grid::make(8)
                                ->schema([
                                    TextEntry::make('product.name')->label('Produk')->columnSpan(['lg' => 2]),
                                    TextEntry::make('description')->label('Deskripsi')->columnSpan(['lg' => 1]),
                                    TextEntry::make('quantity')->label('Qty')->alignCenter()->columnSpan(['lg' => 1]),
                                    TextEntry::make('unit.name')->label('Satuan')->alignCenter()->columnSpan(['lg' => 1]),
                                    TextEntry::make('discount_percent')->label('Disc%')->suffix('%')->alignCenter()->columnSpan(['lg' => 1]),
                                    TextEntry::make('tax.name')->label('Pajak')->default('-')->alignCenter()->columnSpan(['lg' => 1]),
                                    TextEntry::make('unit_price')->label('Harga')->money('IDR')->alignRight()->columnSpan(['lg' => 1]),
                                    TextEntry::make('total_price')->label('Total')->money('IDR')->weight('bold')->alignRight()->columnSpan(['lg' => 1]),
                                ]),
                        ]),
                ]),

                Section::make('Total & Catatan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Group::make([
                                    TextEntry::make('notes')->label('Catatan'),
                                ]),
                                Group::make([
                                    TextEntry::make('sub_total')->label('Subtotal')->money('IDR'),
                                    TextEntry::make('discount_amount')->label('Diskon')->money('IDR'),
                                    TextEntry::make('shipping_cost')->label('Biaya Kirim')->money('IDR'),
                                    TextEntry::make('other_cost')->label('Biaya Lain')->money('IDR'),
                                    TextEntry::make('total_amount')->label('Total Akhir')->money('IDR')->weight('bold')->size('lg'),
                                ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['supplier', 'paymentTerm', 'tags']))
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->searchable()
                    ->label('Nomor')
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn($record) => Pages\ViewTransaction::getUrl(['record' => $record])),
                Tables\Columns\TextColumn::make('supplier.name')->sortable()->label('Vendor'),
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->label('Referensi')
                    ->formatStateUsing(function ($state) {
                        if (is_numeric($state) && strpos(strtoupper((string) $state), 'E') !== false) {
                            return number_format((float) $state, 0, '', '');
                        }
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('date')->date()->sortable()->label('Tanggal'),
                Tables\Columns\TextColumn::make('due_date')->date()->sortable()->label('Tgl. Jatuh Tempo'),
                Tables\Columns\TextColumn::make('paymentTerm.name')->label('Termin')->sortable(),
                Tables\Columns\TextColumn::make('tags.name')->badge()->separator(',')->label('Tag'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match (strtolower($state)) {
                        'draft' => 'Draf',
                        'approved', 'accepted' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'finished' => 'Selesai',
                        default => ucfirst($state),
                    })
                    ->color(fn($state): string => match (strtolower($state)) {
                        'draft' => 'gray',
                        'sent' => 'primary',
                        'ordered' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->label('Status'),
                Tables\Columns\TextColumn::make('dp')
                    ->money('IDR')
                    ->label('DP')
                    ->default(0)
                    ->state(fn() => 0),
                Tables\Columns\TextColumn::make('total_amount')->money('IDR')->sortable()->label('Total'),
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
                    TablesAction::make('confirm')
                        ->label('Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn($record) => $record->update(['status' => 'approved']))
                        ->visible(fn($record) => $record->status === 'draft')
                        ->requiresConfirmation(),
                    TablesAction::make('reject')
                        ->label('Tolak')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn($record) => $record->update(['status' => 'rejected']))
                        ->visible(fn($record) => $record->status === 'draft')
                        ->requiresConfirmation(),
                    TablesAction::make('createOrder')
                        ->label('Buat Pesanan')
                        ->icon('heroicon-o-shopping-cart')
                        ->color('warning')
                        ->action(function ($record) {
                            $record->update(['status' => 'finished']);

                            $order = \App\Models\PurchaseOrder::create([
                                'supplier_id' => $record->supplier_id,
                                'number' => 'PO/' . str_pad((\App\Models\PurchaseOrder::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT),
                                'date' => now(),
                                'due_date' => now()->addDays(30),
                                'status' => 'draft',
                                'total_amount' => $record->total_amount,
                                'reference' => $record->number,
                            ]);

                            foreach ($record->items as $item) {
                                \App\Models\PurchaseOrderItem::create([
                                    'purchase_order_id' => $order->id,
                                    'product_id' => $item->product_id,
                                    'description' => $item->description,
                                    'quantity' => $item->quantity,
                                    'unit_id' => $item->unit_id,
                                    'unit_price' => $item->unit_price,
                                    'tax_id' => $item->tax_id,
                                    'total_price' => $item->total_price,
                                ]);
                            }

                            return redirect(\App\Filament\Resources\PurchaseOrderResource::getUrl('edit', ['record' => $order]));
                        })
                        ->visible(fn($record) => $record->status === 'approved'),
                    TablesAction::make('print')
                        ->label('Cetak')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->url(fn($record) => route('print.purchase-quote', $record))
                        ->openUrlInNewTab(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPurchaseQuotes::route('/'),
            'create' => Pages\CreatePurchaseQuote::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditPurchaseQuote::route('/{record}/edit'),
        ];
    }
}