<?php

namespace App\Filament\Resources\SalesReturns\Schemas;

use App\Models\SalesInvoice;
use App\Models\Product;
use App\Models\Tax;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class SalesReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Informasi Retur')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('sales_invoice_id')
                                            ->relationship('invoice', 'invoice_number')
                                            ->label('Tagihan Penjualan')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (!$state)
                                                    return;

                                                $invoice = SalesInvoice::with(['contact', 'warehouse', 'items.product', 'items.unit'])->find($state);
                                                if (!$invoice)
                                                    return;

                                                $set('contact_id', $invoice->contact_id);
                                                $set('warehouse_id', $invoice->warehouse_id);
                                                $set('reference', $invoice->invoice_number);
                                                $set('tax_inclusive', (bool) $invoice->tax_inclusive);

                                                $items = $invoice->items->map(function ($item) {
                                                    return [
                                                        'product_id' => $item->product_id,
                                                        'product_name' => $item->product?->name ?? '-',
                                                        'unit_id' => $item->unit_id,
                                                        'unit_name' => $item->unit?->name ?? '-',
                                                        'invoice_qty' => $item->qty,
                                                        'returnable_qty' => $item->qty,
                                                        'return_qty' => 0,
                                                        'unit_price' => $item->price,
                                                        'discount_percent' => $item->discount_percent,
                                                        'tax_name' => $item->tax_name,
                                                        'tax_amount' => 0,
                                                        'total_price' => 0,
                                                    ];
                                                })->toArray();

                                                $set('items', $items);
                                                self::updateTotals($get, $set);
                                            }),

                                        TextInput::make('number')
                                            ->label('Nomor Retur')
                                            ->required()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->default(fn() => \App\Models\NumberingSetting::getNextNumber('sales_return') ?? 'SR/00001'),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        Group::make([
                                            Select::make('contact_id')
                                                ->relationship('contact', 'name', fn($query) => $query->whereIn('type', ['customer', 'both']))
                                                ->label('Pelanggan')
                                                ->disabled()
                                                ->dehydrated(),
                                        ])->columnSpan(1),

                                        Group::make([
                                            Select::make('warehouse_id')
                                                ->relationship('warehouse', 'name')
                                                ->label('Gudang Tujuan')
                                                ->required()
                                                ->live()
                                                ->dehydrated(),
                                        ])->columnSpan(1),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        DatePicker::make('date')
                                            ->label('Tanggal Retur')
                                            ->required()
                                            ->default(now()),
                                        TextInput::make('reference')
                                            ->label('Referensi'),
                                    ]),
                            ]),

                        Section::make('Item Retur')
                            ->schema([
                                Toggle::make('tax_inclusive')
                                    ->label('Harga termasuk pajak')
                                    ->inline(false)
                                    ->default(false)
                                    ->live()
                                    ->disabled()
                                    ->dehydrated()
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set)),

                                Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Grid::make(12)
                                            ->schema([
                                                Hidden::make('product_id')->dehydrated(),
                                                Hidden::make('unit_id')->dehydrated(),

                                                TextInput::make('product_name')
                                                    ->label('Produk')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->afterStateHydrated(function (TextInput $component, $state, $record) {
                                                        if (!$state && $record && $record->product) {
                                                            $component->state($record->product->name);
                                                        }
                                                    })
                                                    ->columnSpan(4),

                                                TextInput::make('invoice_qty')
                                                    ->label('Qty Faktur')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->columnSpan(2),

                                                TextInput::make('returnable_qty')
                                                    ->label('Bisa Diretur')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->columnSpan(2),

                                                TextInput::make('return_qty')
                                                    ->label('Qty Retur')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                        $max = (float) $get('returnable_qty');
                                                        $val = (float) $state;
                                                        if ($val > $max) {
                                                            $set('return_qty', $max);
                                                        } elseif ($val < 0) {
                                                            $set('return_qty', 0);
                                                        }
                                                        self::calculateLineTotal($get, $set);
                                                    })
                                                    ->suffixAction(function (Get $get, $livewire) {
                                                        $productId = $get('product_id');
                                                        $warehouseId = $get('../../warehouse_id') ?? $livewire->data['warehouse_id'] ?? null;
                                                        if ($productId && $warehouseId) {
                                                            $stock = \App\Models\Stock::where('product_id', $productId)
                                                                ->where('warehouse_id', $warehouseId)
                                                                ->value('quantity') ?? 0;
                                                            return \Filament\Actions\Action::make('stock')
                                                                ->label((string) $stock)
                                                                ->color($stock > 0 ? 'success' : 'danger')
                                                                ->badge()
                                                                ->disabled();
                                                        }
                                                        return null;
                                                    })
                                                    ->columnSpan(2),

                                                TextInput::make('unit_name')
                                                    ->label('Satuan')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->afterStateHydrated(function (TextInput $component, $state, $record) {
                                                        if (!$state && $record && $record->unit) {
                                                            $component->state($record->unit->name);
                                                        }
                                                    })
                                                    ->columnSpan(2),
                                            ]),

                                        Grid::make(12)
                                            ->schema([
                                                TextInput::make('unit_price')
                                                    ->label('Harga')
                                                    ->numeric()
                                                    ->readOnly()
                                                    ->live()
                                                    ->dehydrated()
                                                    ->columnSpan(3),

                                                TextInput::make('discount_percent')
                                                    ->label('Diskon (%)')
                                                    ->numeric()
                                                    ->readOnly()
                                                    ->live()
                                                    ->dehydrated()
                                                    ->columnSpan(3),

                                                Select::make('tax_name')
                                                    ->label('Pajak')
                                                    ->options(function () {
                                                        $taxes = \App\Models\Tax::pluck('name', 'name')->toArray();
                                                        return ['Bebas Pajak' => 'Bebas Pajak'] + $taxes;
                                                    })
                                                    ->default('Bebas Pajak')
                                                    ->selectablePlaceholder(false)
                                                    ->live()
                                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateLineTotal($get, $set))
                                                    ->columnSpan(3),

                                                Hidden::make('tax_amount')->dehydrated(),

                                                TextInput::make('total_price')
                                                    ->label('Jumlah')
                                                    ->numeric()
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->columnSpan(3),
                                            ]),
                                    ])
                                    ->columnSpanFull()
                                    ->live()
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set)),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Group::make()
                                    ->schema([
                                        Textarea::make('notes')
                                            ->label('Pesan'),
                                        FileUpload::make('attachments')
                                            ->label('Lampiran')
                                            ->multiple()
                                            ->directory('sales-return-attachments'),
                                    ])->columnSpan(1),

                                Group::make()
                                    ->schema([
                                        TextInput::make('sub_total')
                                            ->label('Sub Total')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(),
                                        TextInput::make('tax_amount')
                                            ->label('Total Pajak')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(),
                                        TextInput::make('total_amount')
                                            ->label('Total Retur')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->extraAttributes(['class' => 'font-bold text-lg']),
                                    ])->columnSpan(1),
                            ]),
                    ])
                    ->columnSpan(['lg' => 3]),
            ])
            ->columns(3);
    }

    public static function calculateLineTotal(Get $get, Set $set): void
    {
        $qty = (float) ($get('return_qty') ?? 0);
        $price = (float) ($get('unit_price') ?? 0);
        $discountPercent = (float) ($get('discount_percent') ?? 0);
        $taxInclusive = (bool) ($get('../../tax_inclusive') ?? false);
        $taxName = $get('tax_name');

        $discountAmount = $price * ($discountPercent / 100);
        $discountedPrice = $price - $discountAmount;

        $taxRate = 0;
        if ($taxName) {
            $tax = \App\Models\Tax::where('name', $taxName)->first();
            if ($tax) {
                $taxRate = $tax->rate / 100;
            }
        }

        $taxAmount = 0;
        $totalPrice = 0;

        if ($taxInclusive) {
            $basePrice = $discountedPrice / (1 + $taxRate);
            $taxAmount = ($discountedPrice - $basePrice) * $qty;
            $totalPrice = $discountedPrice * $qty;
        } else {
            $taxAmount = ($discountedPrice * $taxRate) * $qty;
            $totalPrice = ($discountedPrice * $qty) + $taxAmount;
        }

        $set('tax_amount', round($taxAmount, 2));
        $set('total_price', round($totalPrice, 2));

        self::updateTotals($get, $set);
    }

    public static function updateTotals(Get $get, Set $set): void
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
            $items = [];
        }

        $subTotal = 0;
        $totalTax = 0;
        $taxInclusive = (bool) $get($prefix . 'tax_inclusive');

        foreach ($items as $item) {
            $qty = (float) ($item['return_qty'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $discountPercent = (float) ($item['discount_percent'] ?? 0);

            $discountAmount = $price * ($discountPercent / 100);
            $discountedPrice = $price - $discountAmount;

            $taxRate = 0;
            if (isset($item['tax_name']) && $item['tax_name']) {
                $tax = \App\Models\Tax::where('name', $item['tax_name'])->first();
                if ($tax) {
                    $taxRate = $tax->rate / 100;
                }
            }

            if ($taxInclusive) {
                $basePrice = $discountedPrice / (1 + $taxRate);
                $subTotal += $basePrice * $qty;
            } else {
                $subTotal += $discountedPrice * $qty;
            }

            $totalTax += (float) ($item['tax_amount'] ?? 0);
        }

        $totalAmount = $subTotal + $totalTax;

        $set($prefix . 'sub_total', round($subTotal, 2));
        $set($prefix . 'tax_amount', round($totalTax, 2));
        $set($prefix . 'total_amount', round($totalAmount, 2));
    }
}
