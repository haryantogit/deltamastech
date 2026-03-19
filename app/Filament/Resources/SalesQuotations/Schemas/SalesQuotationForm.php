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
                            ->relationship('contact', 'name', fn($query) => $query->whereIn('type', ['customer', 'both']))
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

                         Group::make()
                            ->schema([
                                Repeater::make('items')
                                    ->relationship()
                                    ->label(null)
                                    ->schema([
                                        Grid::make(24)
                                            ->schema([
                                                Select::make('product_id')
                                                    ->relationship('product', 'name', modifyQueryUsing: fn($query) => $query->active())
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
                                                    ->label('Produk')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        if ($product = \App\Models\Product::find($state)) {
                                                            $set('unit_price', number_format($product->sell_price, 0, ',', '.'));
                                                            $set('description', $product->description);
                                                            $set('unit_id', $product->unit_id);
                                                            $set('quantity', 1);

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

                                                            self::calculateItemTotal($set, $get);
                                                        }
                                                    })
                                                    ->columnSpan(6),
                                                Textarea::make('description')
                                                    ->label('Deskripsi')
                                                    ->rows(1)
                                                    ->autosize()
                                                    ->columnSpan(3),
                                                TextInput::make('quantity')
                                                    ->label('Kuantitas')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateItemTotal($set, $get))
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
                                                    ->columnSpan(3),
                                                Select::make('unit_id')
                                                    ->label('Satuan')
                                                    ->relationship('unit', 'name')
                                                    ->placeholder('Pilih')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->searchable()
                                                    ->preload()
                                                    ->columnSpan(2)
                                                    ->live(),

                                                TextInput::make('unit_price')
                                                    ->label('Harga')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        $cleanValue = str_replace(['.', ','], '', $state);
                                                        if (is_numeric($cleanValue)) {
                                                            $set('unit_price', number_format((float) $cleanValue, 0, ',', '.'));
                                                        }
                                                        self::calculateItemTotal($set, $get);
                                                    })
                                                    ->extraAlpineAttributes([
                                                        'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                                    ])
                                                    ->columnSpan(3),
                                                TextInput::make('discount_percent')
                                                    ->label('Diskon (%)')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateItemTotal($set, $get))
                                                    ->columnSpan(2),
                                                Select::make('tax_name')
                                                    ->label('Pajak')
                                                    ->options(function () {
                                                        $taxes = \App\Models\Tax::pluck('name', 'name')->toArray();
                                                        return ['Bebas Pajak' => '...'] + $taxes;
                                                    })
                                                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name === 'Bebas Pajak' ? '...' : $record->name)
                                                    ->default('Bebas Pajak')
                                                    ->selectablePlaceholder(false)
                                                    ->live()
                                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateItemTotal($set, $get))
                                                    ->columnSpan(2),
                                                TextInput::make('total_price')
                                                    ->label('Total')
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->extraAlpineAttributes([
                                                        'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                                    ])
                                                    ->columnSpan(3),
                                            ]),
                                    ])
                                    ->columnSpanFull()
                                    ->live()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotal($get, $set))
                                    ->addActionLabel('Tambah Item'),
                            ])->extraAttributes(['class' => 'w-full overflow-y-visible border rounded-xl bg-gray-50/50 dark:bg-white/5']),
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
                                    ->readOnly()
                                    ->default(0)
                                    ->extraAlpineAttributes([
                                        'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                    ]),

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
                                    ->default(0)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotal($get, $set))
                                    ->extraAlpineAttributes([
                                        'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                    ])
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
                                    ->default(0)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotal($get, $set))
                                    ->extraAlpineAttributes([
                                        'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                    ])
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
                                    ->default(0)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotal($get, $set))
                                    ->extraAlpineAttributes([
                                        'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                    ])
                                    ->hidden(fn(Get $get) => !$get('has_other_cost')),

                                TextInput::make('total_amount')
                                    ->label('Total')
                                    ->readOnly()
                                    ->default(0)
                                    ->extraAlpineAttributes([
                                        'x-mask:dynamic' => '$money($input, ".", ",", 0)',
                                    ])
                                    ->extraAttributes(['class' => 'font-bold text-lg text-primary-600']),
                            ])->columnSpan(1),
                    ]),

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

    public static function calculateItemTotal(Set $set, Get $get): void
    {
        $qty = self::parseNumber($get('quantity'));
        $price = self::parseNumber($get('unit_price'));
        $discount = self::parseNumber($get('discount_percent'));
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
            $set('total_price', number_format($discounted, 0, ',', '.'));
        } else {
            $taxAmount = $discounted * $taxRate;
            $set('tax_amount', $taxAmount);
            $set('total_price', number_format($discounted + $taxAmount, 0, ',', '.'));
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
            $qty = self::parseNumber($item['quantity'] ?? 0);
            $price = self::parseNumber($item['unit_price'] ?? 0);
            $discount = self::parseNumber($item['discount_percent'] ?? 0);
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

        $set($prefix . 'sub_total', number_format($subtotal, 0, ',', '.'));

        $discountAmount = self::parseNumber($get($prefix . 'discount_amount') ?? 0);
        $shipping = self::parseNumber($get($prefix . 'shipping_cost') ?? 0);
        $other = self::parseNumber($get($prefix . 'other_cost') ?? 0);

        $total = $subtotal + $totalTax - $discountAmount + $shipping + $other;

        $set($prefix . 'total_amount', number_format($total, 0, ',', '.'));
    }
}
