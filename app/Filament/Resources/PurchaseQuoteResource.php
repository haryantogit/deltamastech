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
                                    ->relationship('supplier', 'name')
                                    ->required()
                                    ->label('Vendor')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Hidden::make('type')->default('Vendor'),
                                        Hidden::make('payable_account_id')
                                            ->default(fn() => \App\Models\Account::where('code', '2-20100')->first()?->id),
                                        Hidden::make('receivable_account_id')
                                            ->default(fn() => \App\Models\Account::where('code', '1-10100')->first()?->id),
                                        FileUpload::make('photo')
                                            ->label('Foto')
                                            ->image()
                                            ->avatar()
                                            ->columnSpanFull()
                                            ->alignCenter(),
                                        Grid::make(4)
                                            ->schema([
                                                Select::make('salutation')
                                                    ->label('Sapaan')
                                                    ->options([
                                                        'Bapak' => 'Bapak',
                                                        'Ibu' => 'Ibu',
                                                        'Sdr' => 'Sdr',
                                                        'Nona' => 'Nona',
                                                    ])
                                                    ->columnSpan(1),
                                                TextInput::make('name')
                                                    ->label('Nama')
                                                    ->required()
                                                    ->columnSpan(3),
                                            ]),
                                        TextInput::make('company')->label('Perusahaan'),
                                        Grid::make(2)->schema([
                                            TextInput::make('phone')->label('Telepon'),
                                            TextInput::make('email')->label('Email')->email(),
                                        ]),
                                        Grid::make(2)->schema([
                                            TextInput::make('nik')->label('NIK / KTP'),
                                            TextInput::make('npwp')->label('NPWP'),
                                        ]),
                                        Textarea::make('address')->label('Alamat Penagihan'),

                                        Section::make('Rekening Bank')
                                            ->schema([
                                                Repeater::make('bankAccounts')
                                                    ->relationship()
                                                    ->schema([
                                                        TextInput::make('bank_name')->label('Nama Bank'),
                                                        TextInput::make('bank_account_holder')->label('Nama Pemilik'),
                                                        TextInput::make('bank_account_no')->label('Nomor Rekening'),
                                                    ])
                                                    ->addActionLabel('Tambah Rekening Bank')
                                                    ->collapsible()
                                                    ->collapsed(),
                                            ])
                                            ->collapsible(),
                                    ]),
                                TextInput::make('number')
                                    ->label('Nomor')
                                    ->required()
                                    ->default(function () {
                                        $lastQuote = \App\Models\PurchaseQuote::latest('id')->first();
                                        if ($lastQuote && preg_match('/PQ\/(\d{5})/', $lastQuote->number, $matches)) {
                                            return 'PQ/' . str_pad(intval($matches[1]) + 1, 5, '0', STR_PAD_LEFT);
                                        }
                                        return 'PQ/00001';
                                    })
                                    ->readOnly(),
                                DatePicker::make('date')
                                    ->label('Tgl. Transaksi')
                                    ->required()
                                    ->default(now()),
                                DatePicker::make('due_date')
                                    ->label('Kadaluarsa')
                                    ->default(now()->addDays(30)),
                                Select::make('payment_term_id')
                                    ->relationship('paymentTerm', 'name')
                                    ->label('Termin')
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        TextInput::make('days')->numeric()->required(),
                                    ]),
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
                            ])
                            ->collapsible()
                            ->collapsed(), // Hide by default as per screenshot "Sembunyikan Informasi Pengiriman"

                        Section::make('Item Penawaran')
                            ->schema([
                                TextInput::make('barcode_scanner')
                                    ->label('Scan Barcode/SKU')
                                    ->placeholder('Scan Barcode/SKU...')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if (blank($state))
                                            return;

                                        // Sanitize input just in case
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
                                    ->columnSpanFull(),

                                Toggle::make('tax_inclusive')
                                    ->label('Harga termasuk pajak')
                                    ->default(true)
                                    ->reactive()
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set)),

                                Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Select::make('product_id')
                                            ->relationship('product', 'name')
                                            ->label('Produk')
                                            ->createOptionForm([
                                                Section::make()
                                                    ->schema([
                                                        Hidden::make('type')->default('standard'),
                                                        FileUpload::make('image')
                                                            ->label('Sembunyikan Gambar Produk')
                                                            ->image()
                                                            ->maxSize(10240)
                                                            ->maxFiles(5)
                                                            ->multiple()
                                                            ->directory('products')
                                                            ->visibility('public')
                                                            ->columnSpanFull(),
                                                        TextInput::make('name')
                                                            ->label('Nama Produk')
                                                            ->required()
                                                            ->columnSpanFull(),
                                                        Grid::make(3)
                                                            ->schema([
                                                                Select::make('category_id')
                                                                    ->relationship('category', 'name')
                                                                    ->label('Kategori')
                                                                    ->required()
                                                                    ->createOptionForm([
                                                                        TextInput::make('name')->required(),
                                                                    ])
                                                                    ->searchable()
                                                                    ->preload(),
                                                                TextInput::make('sku')
                                                                    ->label('Kode/SKU')
                                                                    ->default(function () {
                                                                        $nextId = (\App\Models\Product::max('id') ?? 0) + 1;
                                                                        return 'SKU/' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
                                                                    })
                                                                    ->unique('products', 'sku')
                                                                    ->dehydrated(),
                                                                Select::make('unit_id')
                                                                    ->relationship('unit', 'name')
                                                                    ->label('Satuan')
                                                                    ->required()
                                                                    ->createOptionForm([
                                                                        TextInput::make('name')->required(),
                                                                        TextInput::make('symbol')->required(),
                                                                    ])
                                                                    ->searchable()->preload(),
                                                            ]),
                                                        Textarea::make('description')
                                                            ->label('Deskripsi')
                                                            ->columnSpanFull(),

                                                        Section::make('Tampilkan pengaturan akun dan pajak')
                                                            ->collapsed()
                                                            ->schema([
                                                                Toggle::make('can_be_purchased')
                                                                    ->label('Saya membeli item ini')
                                                                    ->default(true)
                                                                    ->reactive(),
                                                                TextInput::make('buy_price')
                                                                    ->label('Harga')
                                                                    ->numeric()
                                                                    ->default(0)
                                                                    ->hidden(fn(Get $get) => !$get('can_be_purchased'))
                                                                    ->prefix('Rp'),
                                                                Hidden::make('purchase_account_id')
                                                                    ->default(fn() => \App\Models\Account::where('code', '5-10000')->first()?->id),
                                                                Select::make('purchase_tax_id')
                                                                    ->label('Pajak Pembelian')
                                                                    ->options([
                                                                        'PPN 11%' => 'PPN 11%',
                                                                        'PPN 12%' => 'PPN 12%',
                                                                        'PPH 21' => 'PPH 21',
                                                                        'PPH 23' => 'PPH 23',
                                                                        'Bebas Pajak' => 'Bebas Pajak',
                                                                    ])
                                                                    ->hidden(fn(Get $get) => !$get('can_be_purchased')),

                                                                Toggle::make('can_be_sold')
                                                                    ->label('Saya menjual item ini')
                                                                    ->default(true)
                                                                    ->reactive(),
                                                                TextInput::make('sell_price')
                                                                    ->label('Harga')
                                                                    ->numeric()
                                                                    ->default(0)
                                                                    ->hidden(fn(Get $get) => !$get('can_be_sold'))
                                                                    ->prefix('Rp'),
                                                                Hidden::make('sales_account_id')
                                                                    ->default(fn() => \App\Models\Account::where('code', '4-10000')->first()?->id),
                                                                Select::make('sales_tax_id')
                                                                    ->label('Pajak Penjualan')
                                                                    ->options([
                                                                        'PPN 11%' => 'PPN 11%',
                                                                        'PPN 12%' => 'PPN 12%',
                                                                        'PPH 21' => 'PPH 21',
                                                                        'PPH 23' => 'PPH 23',
                                                                        'Bebas Pajak' => 'Bebas Pajak',
                                                                    ])
                                                                    ->hidden(fn(Get $get) => !$get('can_be_sold')),

                                                                Toggle::make('track_inventory')
                                                                    ->label('Saya melacak inventori item ini')
                                                                    ->default(true)
                                                                    ->reactive(),
                                                                Hidden::make('inventory_account_id')
                                                                    ->default(fn() => \App\Models\Account::where('code', '1-10003')->first()?->id),
                                                            ])
                                                    ])
                                            ])
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get, $component) {
                                                $product = \App\Models\Product::find($state);
                                                if ($product) {
                                                    $price = $product->cost_price ?? $product->buy_price ?? $product->price ?? 0;
                                                    $set('unit_price', $price);
                                                    $set('description', $product->description);
                                                    $set('unit_id', $product->unit_id);

                                                    // Auto-populate tax
                                                    $taxId = $product->purchase_tax_id ?? null;
                                                    $set('tax_id', $taxId);

                                                    // Trigger calculation to update totals immediately
                                                    self::calculateLineTotal($get, $set, $component, ['unit_price' => $price, 'tax_id' => $taxId]);
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
                                            ->hint(function (Get $get) {
                                                $productId = $get('product_id');
                                                // Purchase Quote doesn't have warehouse_id, use null for total stock
                                                if (!$productId)
                                                    return null;

                                                $product = \App\Models\Product::find($productId);
                                                if (!$product || !$product->track_inventory)
                                                    return null;

                                                $stock = $product->getStockForWarehouse();
                                                $minStock = (float) ($product->min_stock ?? 0);
                                                $color = $stock > $minStock ? '#22c55e' : '#ef4444';
                                                return new \Illuminate\Support\HtmlString(
                                                    "<span style=\"padding: 2px 10px; border-radius: 9999px; background-color: {$color}; color: white; font-size: 12px; font-weight: bold; display: inline-block; line-height: 1;\">{$stock}</span>"
                                                );
                                            })
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component)),
                                        Select::make('unit_id')
                                            ->relationship('unit', 'name')
                                            ->label('Satuan')
                                            ->placeholder('Pilih')
                                            ->createOptionForm([
                                                TextInput::make('name')->label('Nama Satuan')->required(),
                                                TextInput::make('symbol')->label('Simbol')->required(),
                                            ])
                                            ->searchable()
                                            ->columnSpan(2),
                                        TextInput::make('discount_percent')
                                            ->label('Diskon (%)')
                                            ->numeric()
                                            ->default(0)
                                            ->suffix('%')
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component)),
                                        TextInput::make('unit_price')
                                            ->label('Harga')
                                            ->numeric()
                                            ->required()
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component)),
                                        Select::make('tax_id')
                                            ->label('Pajak')
                                            ->placeholder('Pilih')
                                            ->options(\App\Models\Tax::pluck('name', 'id')->toArray())
                                            ->default(null)
                                            ->nullable()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get, $component) {
                                                $taxRate = 0;
                                                if ($state) {
                                                    $tax = \App\Models\Tax::find($state);
                                                    $taxRate = $tax ? ($tax->rate / 100) : 0;
                                                }

                                                $price = (float) $get('unit_price');
                                                $qty = (float) $get('quantity');
                                                $discount = (float) $get('discount_percent');

                                                $subtotal = $price * $qty;
                                                $discountAmount = $subtotal * ($discount / 100);
                                                $taxBase = $subtotal - $discountAmount;
                                                $taxAmount = $taxBase * $taxRate;

                                                $set('tax_amount', $taxAmount);
                                                self::calculateLineTotal($get, $set, $component);
                                            }),
                                        Hidden::make('tax_amount'),
                                        TextInput::make('total_price')
                                            ->label('Total')
                                            ->numeric()
                                            ->readOnly()
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
                                        Textarea::make('notes')->label('Pesan')
                                            ->rows(3),
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
                                            ->prefix('Rp'),

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
                                            ->numeric()
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp'),

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
                                            ->numeric()
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp'),

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
                                            ->numeric()
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp'),

                                        TextInput::make('total_amount')
                                            ->label('Total')
                                            ->numeric()
                                            ->readOnly()
                                            ->prefix('Rp')
                                            ->extraAttributes(['class' => 'font-bold text-lg']),
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
        $set('total_price', $total);

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
        // Try all possible paths to find the items repeater
        $items = $get('items') ?? $get('../../items') ?? $get('../../../items') ?? $get('../../../../items') ?? [];

        // If still not an array
        if (!is_array($items)) {
            $items = $get('../../') ?? [];
            if (!is_array($items))
                $items = [];
        }

        $taxInclusive = (bool) $get('tax_inclusive');
        $subTotal = 0;
        $totalTax = 0;
        $taxes = \App\Models\Tax::pluck('rate', 'id')->all();

        foreach ($items as $key => $item) {
            $keysProcessed[] = (string) $key;

            // Apply overrides if key matches
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

            $taxRate = 0;
            if ($taxId && isset($taxes[$taxId])) {
                $taxRate = $taxes[$taxId] / 100;
            }

            $base = $qty * $price;
            $discounted = $base * (1 - ($discountPercent / 100));

            if ($taxInclusive) {
                $itemTax = $discounted - ($discounted / (1 + $taxRate));
                $itemTotal = $discounted;
                $subTotal += ($discounted / (1 + $taxRate));
            } else {
                $itemTax = $discounted * $taxRate;
                $itemTotal = $discounted + $itemTax;
                $subTotal += $discounted;
            }
            $totalTax += $itemTax;
        }

        // Fallback for current edited row if not in items list yet
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

        // Set Final fields
        $set('sub_total', $subTotal);
        $set('../../sub_total', $subTotal);
        $set('../../../sub_total', $subTotal);
        $set('../../../../sub_total', $subTotal);

        $discountAmount = (float) ($get('discount_amount') ?? $get('../../discount_amount') ?? $get('../../../discount_amount') ?? 0);
        $shippingCost = (float) ($get('shipping_cost') ?? $get('../../shipping_cost') ?? $get('../../../shipping_cost') ?? 0);
        $otherCost = (float) ($get('other_cost') ?? $get('../../other_cost') ?? $get('../../../other_cost') ?? 0);

        $grandTotal = $subTotal + $totalTax - $discountAmount + $shippingCost + $otherCost;

        $set('total_amount', $grandTotal);
        $set('../../total_amount', $grandTotal);
        $set('../../../total_amount', $grandTotal);
        $set('../../../../total_amount', $grandTotal);
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
                                TextEntry::make('supplier.name')->label('Pemasok'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('reference')->label('Referensi'),
                            ]),
                    ]),

                Section::make('Item Barang / Jasa')->schema([
                    RepeatableEntry::make('items')
                        ->schema([
                            Grid::make(8)
                                ->schema([
                                    TextEntry::make('product.name')->label('Produk')->columnSpan(2),
                                    TextEntry::make('description')->label('Deskripsi')->columnSpan(1),
                                    TextEntry::make('quantity')->label('Qty')->alignCenter()->columnSpan(1),
                                    TextEntry::make('unit.name')->label('Satuan')->alignCenter()->columnSpan(1),
                                    TextEntry::make('discount_percent')->label('Disc%')->suffix('%')->alignCenter()->columnSpan(1),
                                    TextEntry::make('tax.name')->label('Pajak')->default('-')->alignCenter()->columnSpan(1),
                                    TextEntry::make('unit_price')->label('Harga')->money('IDR')->alignRight()->columnSpan(1),
                                    TextEntry::make('total_price')->label('Total')->money('IDR')->weight('bold')->alignRight()->columnSpan(1),
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
                Tables\Columns\TextColumn::make('number')->searchable()->label('Nomor'),
                Tables\Columns\TextColumn::make('supplier.name')->sortable()->label('Pemasok'),
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
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'finished' => 'Selesai',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'draft' => 'gray',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'finished' => 'info',
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
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name', modifyQueryUsing: fn($query) => $query->where('type', 'vendor'))
                    ->label('Pemasok')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draf',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'finished' => 'Selesai',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
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
            'index' => Pages\ListPurchaseQuotes::route('/'),
            'create' => Pages\CreatePurchaseQuote::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditPurchaseQuote::route('/{record}/edit'),
        ];
    }
}