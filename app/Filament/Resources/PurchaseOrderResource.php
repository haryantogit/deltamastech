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
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

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
                                                    ->columns(3)
                                                    ->label('Bank accounts'),
                                            ])
                                            ->collapsible()
                                            ->collapsed()
                                            ->compact(),
                                    ]),
                                TextInput::make('number')
                                    ->required()
                                    ->label('Nomor')
                                    ->readOnly()
                                    ->default(function () {
                                        $lastOrder = \App\Models\PurchaseOrder::latest('id')->first();
                                        if ($lastOrder && preg_match('/PO\/(\d{5})/', $lastOrder->number, $matches)) {
                                            return 'PO/' . str_pad(intval($matches[1]) + 1, 5, '0', STR_PAD_LEFT);
                                        }
                                        return 'PO/00001';
                                    }),
                                DatePicker::make('date')
                                    ->required()
                                    ->label('Tgl. Transaksi')
                                    ->default(now()),
                                DatePicker::make('due_date')
                                    ->label('Tgl. Jatuh Tempo')
                                    ->default(now()->addDays(30)),
                                Select::make('payment_term_id')
                                    ->relationship('paymentTerm', 'name')
                                    ->label('Termin')
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        TextInput::make('days')->numeric()->required(),
                                    ]),
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
                                    ->createOptionForm([
                                        TextInput::make('name')->label('Nama Tag')->required(),
                                    ])
                                    ->preload(),
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
                                    ->columnSpanFull(),

                                Toggle::make('tax_inclusive')
                                    ->label('Harga termasuk pajak')
                                    ->inline()
                                    ->default(true)
                                    ->reactive()
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set)),

                                Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Select::make('product_id')
                                            ->relationship('product', 'name', modifyQueryUsing: fn($query) => $query->active())
                                            ->label('Produk')
                                            ->preload()
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

                                                        Section::make('Pengaturan Akun dan Pajak')
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
                                                                Select::make('purchase_account_id')
                                                                    ->label('Akun Pembelian')
                                                                    ->relationship('purchaseAccount', 'name')
                                                                    ->default(fn() => \App\Models\Account::where('code', '5-10000')->first()?->id)
                                                                    ->hidden(fn(Get $get) => !$get('can_be_purchased'))
                                                                    ->searchable()
                                                                    ->preload(),

                                                                // Select::make('purchase_account_id')
                                                                //     ->relationship('purchaseAccount', 'name')
                                                                //     ->label('Akun Pembelian')
                                                                //     ->hidden(fn(Get $get) => !$get('can_be_purchased'))
                                                                //     ->searchable()
                                                                //     ->preload(),
                                                                Select::make('purchase_tax_id')
                                                                    ->label('Pajak Pembelian')
                                                                    ->options([
                                                                        0 => 'Tanpa Pajak',
                                                                        11 => 'PPN 11%',
                                                                        12 => 'PPN 12%',
                                                                        21 => 'PPH 21',
                                                                        23 => 'PPH 23',
                                                                    ])
                                                                    ->hidden(fn(Get $get) => !$get('can_be_purchased'))
                                                                    ->searchable()
                                                                    ->preload(),

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
                                                                Select::make('sales_account_id')
                                                                    ->label('Akun Penjualan')
                                                                    ->relationship('salesAccount', 'name')
                                                                    ->default(fn() => \App\Models\Account::where('code', '4-10000')->first()?->id)
                                                                    ->hidden(fn(Get $get) => !$get('can_be_sold'))
                                                                    ->searchable()
                                                                    ->preload(),
                                                                Select::make('sales_tax_id')
                                                                    ->label('Pajak Penjualan')
                                                                    ->options([
                                                                        0 => 'Tanpa Pajak',
                                                                        11 => 'PPN 11%',
                                                                        12 => 'PPN 12%',
                                                                        21 => 'PPH 21',
                                                                        23 => 'PPH 23',
                                                                    ])
                                                                    ->hidden(fn(Get $get) => !$get('can_be_sold'))
                                                                    ->searchable()
                                                                    ->preload(),

                                                                Toggle::make('track_inventory')
                                                                    ->label('Saya melacak inventori item ini')
                                                                    ->default(true)
                                                                    ->reactive(),
                                                                Select::make('inventory_account_id')
                                                                    ->label('Akun Persediaan')
                                                                    ->relationship('inventoryAccount', 'name')
                                                                    ->default(fn() => \App\Models\Account::where('code', '1-10003')->first()?->id)
                                                                    ->hidden(fn(Get $get) => !$get('track_inventory'))
                                                                    ->searchable()
                                                                    ->preload(),
                                                            ])
                                                            ->collapsible()
                                                            ->collapsed(),
                                                    ])
                                            ])
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get, $component) {
                                                $product = \App\Models\Product::find($state);
                                                if ($product) {
                                                    if ($product->is_fixed_asset) {
                                                        $price = $product->purchase_price ?? 0;
                                                    } else {
                                                        $price = $product->cost_price ?? $product->buy_price ?? $product->price ?? 0;
                                                    }

                                                    $set('unit_price', $price);
                                                    $set('description', $product->description);
                                                    $set('unit_id', $product->unit_id);

                                                    // Auto-populate tax
                                                    $taxId = $product->purchase_tax_id;
                                                    if ($taxId && !is_numeric($taxId)) {
                                                        $taxId = \App\Models\Tax::where('name', $taxId)->first()?->id;
                                                    }
                                                    $set('tax_id', $taxId);

                                                    // Trigger calculation to update totals immediately
                                                    self::calculateLineTotal($get, $set, $component, [
                                                        'unit_price' => $price,
                                                        'tax_id' => $taxId
                                                    ]);
                                                }
                                            })
                                            ->columnSpan(3),
                                        TextInput::make('description')
                                            ->label('Deskripsi')
                                            ->columnSpan(2),
                                        TextInput::make('quantity')
                                            ->label('Kuantitas')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->live(debounce: 500)
                                            ->hint(function (Get $get) {
                                                $productId = $get('product_id');
                                                $warehouseId = $get('../../warehouse_id');
                                                if (!$productId)
                                                    return null;

                                                $product = \App\Models\Product::find($productId);
                                                if (!$product || !$product->track_inventory)
                                                    return null;

                                                $stock = $product->getStockForWarehouse($warehouseId);
                                                $requestedQty = (float) $get('quantity');
                                                $color = $stock >= $requestedQty ? '#22c55e' : '#ef4444';
                                                return new \Illuminate\Support\HtmlString(
                                                    "<span style='background-color: {$color}; color: white; padding: 2px 8px; font-size: 0.75rem; font-weight: bold; border-radius: 9999px;'>{$stock}</span>"
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
                                            ->relationship('tax', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->default(null)
                                            ->nullable()
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
                                                $taxBase = $subtotal - $discountAmount; // This is raw total before tax
                                    
                                                // calculateLineTotal handles inclusive/exclusive logic for tax amount
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
                                        Textarea::make('notes')
                                            ->label('Pesan')
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
                                            ->label(null)
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
                                            ->label(null)
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
                                            ->label(null)
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
                                            ->prefix('Rp'),

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
                                            ->numeric()
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp'),

                                        TextInput::make('balance_due')
                                            ->label('Sisa Tagihan')
                                            ->numeric()
                                            ->readOnly()
                                            ->prefix('Rp')
                                            ->dehydrated(false), // Not saved to DB, just for display
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
        $taxInclusive = (bool) $get('tax_inclusive');

        $taxRate = 0;
        if ($taxId) {
            $tax = \App\Models\Tax::find($taxId);
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

        $set($prefix . 'sub_total', $subTotal);

        $discountAmount = (float) ($get($prefix . 'discount_amount') ?? 0);
        $shippingCost = (float) ($get($prefix . 'shipping_cost') ?? 0);
        $otherCost = (float) ($get($prefix . 'other_cost') ?? 0);
        $downPayment = (float) ($get($prefix . 'down_payment') ?? 0);

        if ($subTotal > 0 && $discountAmount > 0) {
            $totalTaxAmount = $totalTaxAmount * (($subTotal - $discountAmount) / $subTotal);
        }

        $set($prefix . 'tax_amount', $totalTaxAmount);

        $grandTotal = ($subTotal - $discountAmount) + $totalTaxAmount + $shippingCost + $otherCost;
        $balanceDue = $grandTotal - $downPayment;

        $set($prefix . 'total_amount', $grandTotal);
        $set($prefix . 'balance_due', $balanceDue);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(3)
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
                                ])->columnSpan(1),

                                Group::make([
                                    TextEntry::make('number')->label('NOMOR'),
                                    TextEntry::make('due_date')->label('TGL JATUH TEMPO')->date('d/m/Y'),
                                    TextEntry::make('reference')->label('REFERENSI')->default(fn($record) => $record->number),
                                ])->columnSpan(1),

                                Group::make([
                                    TextEntry::make('date')->label('TGL TRANSAKSI')->date('d/m/Y'),
                                    TextEntry::make('warehouse.name')->label('GUDANG')->default('Unassigned')->color('primary'),
                                    TextEntry::make('tags.name')
                                        ->label('TAG')
                                        ->badge()
                                        ->separator(','),
                                ])->columnSpan(1),
                            ]),

                        Section::make()
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->label('')
                                    ->schema([
                                        Grid::make(9)
                                            ->schema([
                                                TextEntry::make('product.name')
                                                    ->label('PRODUK')
                                                    ->formatStateUsing(fn($record) => $record->product?->sku . ' - ' . $record->product?->name)
                                                    ->color('primary')
                                                    ->columnSpan(2),
                                                TextEntry::make('description')->label('DESKRIPSI')->default('-')->columnSpan(1),
                                                TextEntry::make('quantity')->label('KUANTITAS')->alignRight()->columnSpan(1),
                                                TextEntry::make('unit.name')->label('SATUAN')->columnSpan(1),
                                                TextEntry::make('discount_percent')
                                                    ->label('DISKON')
                                                    ->formatStateUsing(fn($state) => number_format($state, 2) . '%')
                                                    ->alignRight()
                                                    ->columnSpan(1),
                                                TextEntry::make('unit_price')->label('HARGA')->money('IDR')->alignRight()->columnSpan(1),
                                                TextEntry::make('tax.name')->label('PAJAK')->default('-')->alignRight()->columnSpan(1),
                                                TextEntry::make('total_price')
                                                    ->label('TOTAL')
                                                    ->money('IDR')
                                                    ->alignRight()
                                                    ->weight('bold')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Grid::make(9)
                                    ->schema([
                                        TextEntry::make('total_quantity')
                                            ->label('Total Kuantitas')
                                            ->state(fn($record) => $record->items->sum('quantity'))
                                            ->weight('bold')
                                            ->alignRight()
                                            ->columnStart(4),
                                    ]),
                            ])
                            ->compact(),

                        Grid::make(2)
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
                                ])->columnSpan(1),
                                Group::make([
                                    Grid::make(2)
                                        ->schema([
                                            TextEntry::make('sub_total')
                                                ->label('Sub total')
                                                ->money('IDR')
                                                ->alignRight(),
                                            TextEntry::make('spacer1')->label('')->state('')->hidden(),

                                            TextEntry::make('tax_amount')
                                                ->label('PPN')
                                                ->money('IDR')
                                                ->alignRight(),
                                            TextEntry::make('spacer2')->label('')->state('')->hidden(),

                                            TextEntry::make('discount_amount')
                                                ->label('Diskon')
                                                ->money('IDR')
                                                ->color('danger')
                                                ->formatStateUsing(fn($state) => "- " . number_format($state, 0, ',', '.'))
                                                ->alignRight()
                                                ->visible(fn($record) => $record->discount_amount > 0),
                                            TextEntry::make('spacer3')->label('')->state('')->hidden()
                                                ->visible(fn($record) => $record->discount_amount > 0),

                                            TextEntry::make('shipping_cost')
                                                ->label('Biaya pengiriman')
                                                ->money('IDR')
                                                ->alignRight()
                                                ->visible(fn($record) => $record->shipping_cost > 0),
                                            TextEntry::make('spacer4')->label('')->state('')->hidden()
                                                ->visible(fn($record) => $record->shipping_cost > 0),

                                            TextEntry::make('total_amount')
                                                ->label('Total')
                                                ->money('IDR')
                                                ->weight('bold')
                                                ->size('lg')
                                                ->alignRight(),
                                            TextEntry::make('spacer5')->label('')->state('')->hidden(),

                                            TextEntry::make('balance_due')
                                                ->label('Sisa Tagihan')
                                                ->money('IDR')
                                                ->weight('bold')
                                                ->size('lg')
                                                ->color('primary')
                                                ->alignRight(),
                                            TextEntry::make('spacer6')->label('')->state('')->hidden(),
                                        ])
                                        ->columns(2),
                                ])->columnSpan(1),
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
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Gudang')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
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
                    ->searchable()
                    ->toggleable(),
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
                    ->color(fn(string $state): string => match ($state) {
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
                    ->money('IDR')
                    ->sortable()
                    ->alignRight()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name', modifyQueryUsing: fn($query) => $query->where('type', 'vendor'))
                    ->label('Pemasok')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'ordered' => 'Dipesan',
                        'partial_received' => 'Sebagian Diterima',
                        'received' => 'Diterima',
                        'partial_billed' => 'Sebagian Ditagih',
                        'billed' => 'Ditagih',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->label('Status'),
            ])
            ->defaultSort('date', 'desc')
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('createDelivery')
                        ->label('Buat Pengiriman')
                        ->icon('heroicon-o-truck')
                        ->color('success')
                        ->url(fn($record) => \App\Filament\Resources\PurchaseDeliveryResource::getUrl('create', ['purchase_order_id' => $record->id]))
                        ->visible(fn($record) => in_array($record->status, ['ordered', 'partial_received', 'approved'])),

                    Action::make('createInvoice')
                        ->label('Buat Tagihan')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->url(fn($record) => \App\Filament\Resources\PurchaseInvoiceResource::getUrl('create', ['purchase_order_id' => $record->id]))
                        ->visible(fn($record) => in_array($record->status, ['received', 'partial_received'])),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
