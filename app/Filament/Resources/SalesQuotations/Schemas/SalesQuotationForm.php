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
use Filament\Actions\Action;

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
                            ->preload(),
                        TextInput::make('number')
                            ->label('Nomor')
                            ->required()
                            ->unique('sales_quotations', 'number', ignoreRecord: true)
                            ->default(fn() => \App\Models\NumberingSetting::getNextNumber('sales_quotation') ?? 'QU/' . str_pad((\App\Models\SalesQuotation::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT))
                            ->readOnly(),
                        DatePicker::make('date')
                            ->label('Tgl. Transaksi')
                            ->required()
                            ->default(now()),
                        DatePicker::make('expiry_date')
                            ->label('Kadaluarsa')
                            ->default(now()->addDays(30)),
                        Grid::make(3)
                            ->schema([
                                Select::make('payment_term_id')
                                    ->label('Termin')
                                    ->relationship('paymentTerm', 'name')
                                    ->preload()
                                    ->searchable(),
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
                                    ->columnSpan(2),

                                Toggle::make('tax_inclusive')
                                    ->label('Harga termasuk pajak')
                                    ->inline(false)
                                    ->default(false)
                                    ->reactive()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotal($get, $set))
                                    ->columnSpan(1)
                                    ->extraAttributes(['class' => 'mt-8']),
                            ]),

                        Repeater::make('items')
                            ->relationship()
                            ->label(null)
                            ->schema([
                                Select::make('product_id')
                                    ->relationship('product', 'name', modifyQueryUsing: fn($query) => $query->active())
                                    ->label('Produk')
                                    ->required()
                                    ->searchable()
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
                                    ->suffixAction(
                                        Action::make('checkStock')
                                            ->button()
                                            ->size('sm')
                                            ->color(function (Get $get, $state) {
                                                $productId = $get('product_id');
                                                if (!$productId)
                                                    return 'gray';
                                                $product = \App\Models\Product::find($productId);
                                                if (!$product || !$product->track_inventory)
                                                    return 'gray';
                                                $stock = (float) $product->getStockForWarehouse();
                                                $requestedQty = (float) $state;
                                                return ($stock < $requestedQty || $stock <= 0) ? 'danger' : 'success';
                                            })
                                            ->label(function (Get $get) {
                                                $productId = $get('product_id');
                                                if (!$productId)
                                                    return '0';
                                                $product = \App\Models\Product::find($productId);
                                                if (!$product || !$product->track_inventory)
                                                    return '0';
                                                $stock = $product->getStockForWarehouse();
                                                return number_format($stock);
                                            })
                                    )
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateItemTotal($set, $get))
                                    ->columnSpan(2),
                                Select::make('unit_id')
                                    ->label('Satuan')
                                    ->relationship('unit', 'name')
                                    ->placeholder('Pilih')
                                    ->columnSpan(1),
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
                                    ->readOnly()
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

        $subtotal = 0;
        $totalTax = 0;
        $taxes = \App\Models\Tax::pluck('rate', 'name')->toArray();
        $taxInclusive = (bool) $get($prefix . 'tax_inclusive');

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

        $set($prefix . 'sub_total', $subtotal);

        $discountAmount = (float) ($get($prefix . 'discount_amount') ?? 0);
        $shipping = (float) ($get($prefix . 'shipping_cost') ?? 0);
        $other = (float) ($get($prefix . 'other_cost') ?? 0);

        $total = $subtotal + $totalTax - $discountAmount + $shipping + $other;

        $set($prefix . 'total_amount', $total);
    }
}
