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


                                        TextInput::make('invoice_number')
                                            ->label('Nomor')
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->default(function () {
                                                $last = \App\Models\SalesInvoice::latest('id')->first();
                                                if ($last && preg_match('/INV\/(\d{5})/', $last->invoice_number, $matches)) {
                                                    return 'INV/' . str_pad(intval($matches[1]) + 1, 5, '0', STR_PAD_LEFT);
                                                }
                                                return 'INV/00001';
                                            }),
                                        DatePicker::make('transaction_date')
                                            ->label('Tgl. Transaksi')
                                            ->required()
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


                                        Select::make('warehouse_id')
                                            ->relationship('warehouse', 'name')
                                            ->label('Gudang')
                                            ->searchable()
                                            ->preload()
                                            ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('sales_order_id')))
                                            ->dehydrated(),
                                        TextInput::make('warehouse_name')
                                            ->label('Gudang')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->hidden(fn(Get $get, string $operation) => $operation !== 'create' || !filled($get('sales_order_id'))),


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
                                Grid::make(2)
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
                                            }),
                                        Toggle::make('tax_inclusive')
                                            ->label('Harga termasuk pajak')
                                            ->inline(false)
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set)),
                                    ]),

                                Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Select::make('product_id_select')
                                            ->label('Produk')
                                            ->relationship('product', 'name', modifyQueryUsing: fn($query) => $query->active())
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpan(3)
                                            ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('../../sales_order_id')))
                                            ->dehydrated(false)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                $set('product_id', $state);
                                                if ($product = \App\Models\Product::with('unit')->find($state)) {
                                                    $set('product_name', $product->name);
                                                    $set('description', $product->description);
                                                    $set('unit_id', $product->unit_id);
                                                    $set('unit_name', $product->unit?->name ?? '');
                                                    $set('price', $product->sell_price ?? 0);
                                                }
                                            }),
                                        TextInput::make('product_name')
                                            ->label('Produk')
                                            ->disabled()
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
                                            ->default(0)
                                            ->required()
                                            ->live(onBlur: true)
                                            ->hint(function (Get $get) {
                                                $productId = $get('product_id');
                                                $warehouseId = $get('../../warehouse_id');
                                                if (!$productId)
                                                    return null;
                                                $product = \App\Models\Product::find($productId);
                                                if (!$product || !$product->track_inventory)
                                                    return null;
                                                $stock = $product->getStockForWarehouse($warehouseId);
                                                $requestedQty = (float) $get('qty');
                                                $color = $stock >= $requestedQty ? '#22c55e' : '#ef4444';
                                                return new \Illuminate\Support\HtmlString("<span style='background-color: {$color}; color: white; padding: 2px 8px; font-size: 0.75rem; font-weight: bold; border-radius: 9999px;'>{$stock}</span>");
                                            })
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component)),

                                        Select::make('unit_id_select')
                                            ->label('Satuan')
                                            ->placeholder('Pilih')
                                            ->relationship('unit', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->columnSpan(2)
                                            ->hidden(fn(Get $get, string $operation) => $operation === 'create' && filled($get('../../sales_order_id')))
                                            ->dehydrated(false)->live()->afterStateUpdated(fn($state, Set $set) => $set('unit_id', $state)),
                                        TextInput::make('unit_name')
                                            ->label('Satuan')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpan(2)
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
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Set $set, Get $get, $component) => self::calculateLineTotal($get, $set, $component)),
                                        TextInput::make('tax_name')
                                            ->label('Pajak')
                                            ->disabled()
                                            ->dehydrated(),
                                        TextInput::make('subtotal')
                                            ->label('Total')
                                            ->numeric()
                                            ->disabled()
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
                                        TextInput::make('discount_total')
                                            ->label('Diskon')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp'),
                                        TextInput::make('tax_amount')
                                            ->label('Pajak')
                                            ->numeric()
                                            ->readOnly()
                                            ->prefix('Rp'),
                                        TextInput::make('shipping_cost')
                                            ->label('Biaya Pengiriman')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp'),
                                        TextInput::make('other_cost')
                                            ->label('Biaya Transaksi')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp'),
                                        TextInput::make('total_amount')
                                            ->label('Total')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->prefix('Rp'),
                                        TextInput::make('down_payment')
                                            ->label('Uang Muka')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set))
                                            ->prefix('Rp'),
                                        TextInput::make('balance_due')
                                            ->label('Sisa Tagihan')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->prefix('Rp'),
                                    ])->columns(1),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function calculateLineTotal(Get $get, Set $set, $component = null): void
    {
        $qty = (float) $get('qty');
        $price = (float) $get('price');
        $discountPercent = (float) $get('discount_percent');

        $subtotal = $qty * $price;
        $discountAmount = $subtotal * ($discountPercent / 100);
        $lineTotal = $subtotal - $discountAmount;

        $set('subtotal', $lineTotal);
        self::updateTotals($get, $set);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];
        $subTotal = 0;
        foreach ($items as $item) {
            $subTotal += (float) ($item['subtotal'] ?? 0);
        }

        $discountTotal = (float) ($get('discount_total') ?? 0);
        $shippingCost = (float) ($get('shipping_cost') ?? 0);
        $otherCost = (float) ($get('other_cost') ?? 0);
        $downPayment = (float) ($get('down_payment') ?? 0);

        $totalAmount = $subTotal - $discountTotal + $shippingCost + $otherCost;
        $balanceDue = $totalAmount - $downPayment;

        $set('sub_total', $subTotal);
        $set('total_amount', $totalAmount);
        $set('balance_due', $balanceDue);
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
