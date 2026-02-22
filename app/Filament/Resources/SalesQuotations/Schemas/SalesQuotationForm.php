<?php

namespace App\Filament\Resources\SalesQuotations\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class SalesQuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Informasi Utama')
                    ->schema([
                        Select::make('contact_id')
                            ->label('Pelanggan')
                            ->relationship('contact', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Hidden::make('type')->default('Customer'),
                                Hidden::make('payable_account_id')
                                    ->default(fn() => \App\Models\Account::where('code', '2-20100')->first()?->id),
                                Hidden::make('receivable_account_id')
                                    ->default(fn() => \App\Models\Account::where('code', '1-10001')->first()?->id),
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
                            ->unique('sales_quotations', 'number', ignoreRecord: true)
                            ->default(fn() => 'QU/' . str_pad((\App\Models\SalesQuotation::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT))
                            ->readOnly(),
                        DatePicker::make('date')
                            ->label('Tgl. Transaksi')
                            ->required()
                            ->default(now()),
                        DatePicker::make('expiry_date')
                            ->label('Kadaluarsa')
                            ->default(now()->addDays(30)),
                        Select::make('payment_term_id')
                            ->label('Termin')
                            ->relationship('paymentTerm', 'name')
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                TextInput::make('days')->numeric()->required(),
                            ]),
                        TextInput::make('reference')
                            ->label('Referensi'),
                        Select::make('tags')
                            ->label('Tag')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')->label('Nama Tag')->required(),
                            ]),
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
                    ->collapsed(),

                Section::make('Items')
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
                                            'tax_name' => 'Tanpa Pajak',
                                            'tax_amount' => 0,
                                            'total_price' => $price,
                                        ];
                                    }

                                    $set('items', $items);
                                    $set('barcode_scanner', null);

                                    // Delay updateTotals to ensure state is committed
                                    self::updateTotal($get, $set);

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
                            ->inline()
                            ->default(true)
                            ->reactive()
                            ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotal($get, $set)),

                        Repeater::make('items')
                            ->relationship()
                            ->label(null)
                            ->schema([
                                Select::make('product_id')
                                    ->relationship('product', 'name', modifyQueryUsing: fn($query) => $query->active())
                                    ->label('Produk')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
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
                                                            ->searchable()
                                                            ->preload(),
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
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($product = \App\Models\Product::find($state)) {
                                            $set('unit_price', $product->sell_price);
                                            $set('description', $product->description);
                                            $set('unit_id', $product->unit_id);

                                            // Auto-populate tax
                                            $taxName = null;
                                            if ($product->sales_tax_id) {
                                                // Handle if sales_tax_id is an ID or a name
                                                if (is_numeric($product->sales_tax_id)) {
                                                    $tax = \App\Models\Tax::find($product->sales_tax_id);
                                                    $taxName = $tax ? $tax->name : null;
                                                } else {
                                                    $taxName = $product->sales_tax_id;
                                                }
                                            }
                                            $set('tax_name', $taxName);

                                            self::calculateItemTotal($set, $get);
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
                                    ->live()
                                    ->hint(function (Get $get) {
                                        $productId = $get('product_id');
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
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateItemTotal($set, $get))
                                    ->columnSpan(1),
                                Select::make('unit_id')
                                    ->label('Satuan')
                                    ->relationship('unit', 'name')
                                    ->placeholder('Pilih')
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        TextInput::make('symbol')->required(),
                                    ])
                                    ->columnSpan(2),
                                TextInput::make('discount_percent')
                                    ->label('Diskon (%)')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%')
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateItemTotal($set, $get))
                                    ->columnSpan(1),
                                TextInput::make('unit_price')
                                    ->label('Harga')
                                    ->numeric()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateItemTotal($set, $get))
                                    ->columnSpan(1),
                                Select::make('tax_name')
                                    ->label('Pajak')
                                    ->options(function () {
                                        return \App\Models\Tax::pluck('name', 'name')->toArray();
                                    })
                                    ->placeholder('Pilih')
                                    ->default(null)
                                    ->nullable()
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateItemTotal($set, $get))
                                    ->columnSpan(1),
                                Hidden::make('tax_amount'),
                                TextInput::make('total_price')
                                    ->label('Total')
                                    ->numeric()
                                    ->readOnly()
                                    ->columnSpan(1),
                            ])
                            ->columns(12)
                            ->live()
                            ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotal($get, $set))
                            ->addActionLabel('Tambah Item'),
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
                                    ->directory('sales-quotations')
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

                                Toggle::make('has_discount_amount')
                                    ->label('Tambahan Diskon')
                                    ->inline()
                                    ->default(fn($get) => (float) ($get('discount_amount') ?? 0) > 0)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if (!$state) {
                                            $set('discount_amount', 0);
                                            self::updateTotal($get, $set);
                                        }
                                    }),
                                TextInput::make('discount_amount')
                                    ->label('Nominal Diskon')
                                    ->numeric()
                                    ->default(0)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotal($get, $set))
                                    ->prefix('Rp')
                                    ->hidden(fn(Get $get) => !$get('has_discount_amount')),

                                Toggle::make('has_shipping_cost')
                                    ->label('Biaya Pengiriman')
                                    ->inline()
                                    ->default(fn($get) => (float) ($get('shipping_cost') ?? 0) > 0)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if (!$state) {
                                            $set('shipping_cost', 0);
                                            self::updateTotal($get, $set);
                                        }
                                    }),
                                TextInput::make('shipping_cost')
                                    ->label('Nominal Pengiriman')
                                    ->numeric()
                                    ->default(0)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotal($get, $set))
                                    ->prefix('Rp')
                                    ->hidden(fn(Get $get) => !$get('has_shipping_cost')),

                                Toggle::make('has_other_cost')
                                    ->label('Biaya Lainnya')
                                    ->inline()
                                    ->default(fn($get) => (float) ($get('other_cost') ?? 0) > 0)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if (!$state) {
                                            $set('other_cost', 0);
                                            self::updateTotal($get, $set);
                                        }
                                    }),
                                TextInput::make('other_cost')
                                    ->label('Nominal Biaya Lain')
                                    ->numeric()
                                    ->default(0)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotal($get, $set))
                                    ->prefix('Rp')
                                    ->hidden(fn(Get $get) => !$get('has_other_cost')),

                                TextInput::make('total_amount')
                                    ->label('Total')
                                    ->numeric()
                                    ->readOnly()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->extraAttributes(['class' => 'font-bold text-lg']),
                            ])->columnSpan(1),
                    ]),

            ]);
    }

    public static function calculateItemTotal(Set $set, Get $get): void
    {
        $qty = (float) $get('quantity');
        $price = (float) $get('unit_price');
        $discount = (float) $get('discount_percent');
        $taxName = $get('tax_name');

        $taxRate = 0;
        if ($taxName) {
            $tax = \App\Models\Tax::where('name', $taxName)->first();
            $taxRate = $tax ? ($tax->rate / 100) : 0;
        }

        $base = $qty * $price;
        $discounted = $base * (1 - ($discount / 100));

        if ($get('../../tax_inclusive')) {
            $taxAmount = $discounted - ($discounted / (1 + $taxRate));
            $set('tax_amount', $taxAmount);
            $set('total_price', $discounted);
        } else {
            $taxAmount = $discounted * $taxRate;
            $set('tax_amount', $taxAmount);
            $set('total_price', $discounted + $taxAmount);
        }

        self::updateTotal($get, $set);
    }

    public static function updateTotal(Get $get, Set $set): void
    {
        $items = $get('items') ?? $get('../../items') ?? $get('../../../items') ?? $get('../../../../items') ?? [];
        $subtotal = 0;
        $totalTax = 0;

        $taxes = \App\Models\Tax::pluck('rate', 'name')->toArray();
        $taxInclusive = $get('tax_inclusive') ?? $get('../../tax_inclusive') ?? $get('../../../tax_inclusive') ?? false;

        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $discount = (float) ($item['discount_percent'] ?? 0);
            $taxName = $item['tax_name'] ?? 'Tanpa Pajak';

            $taxRate = (isset($taxes[$taxName])) ? ($taxes[$taxName] / 100) : 0;

            $base = $qty * $price;
            $discounted = $base * (1 - ($discount / 100));

            if ($taxInclusive) {
                $totalTax += ($discounted - ($discounted / (1 + $taxRate)));
                $subtotal += ($discounted / (1 + $taxRate));
            } else {
                $taxAmount = $discounted * $taxRate;
                $subtotal += $discounted;
                $totalTax += $taxAmount;
            }
        }

        $set('sub_total', $subtotal);
        $set('../../sub_total', $subtotal);
        $set('../../../sub_total', $subtotal);
        $set('../../../../sub_total', $subtotal);

        $discountAmount = (float) ($get('discount_amount') ?? $get('../../discount_amount') ?? $get('../../../discount_amount') ?? 0);
        $shipping = (float) ($get('shipping_cost') ?? $get('../../shipping_cost') ?? $get('../../../shipping_cost') ?? 0);
        $other = (float) ($get('other_cost') ?? $get('../../other_cost') ?? $get('../../../other_cost') ?? 0);

        $total = $subtotal + $totalTax - $discountAmount + $shipping + $other;

        $set('total_amount', $total);
        $set('../../total_amount', $total);
        $set('../../../total_amount', $total);
        $set('../../../../total_amount', $total);
    }
}
