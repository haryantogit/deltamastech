<?php

namespace App\Filament\Resources\PurchaseReturns\Schemas;

use App\Models\PurchaseInvoice;
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

class PurchaseReturnForm
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
                                        Select::make('purchase_invoice_id')
                                            ->relationship('invoice', 'number')
                                            ->label('Tagihan Pembelian')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (!$state)
                                                    return;

                                                $invoice = PurchaseInvoice::with(['supplier', 'warehouse', 'items.product', 'items.unit'])->find($state);
                                                if (!$invoice)
                                                    return;

                                                $set('supplier_id', $invoice->supplier_id);
                                                $set('warehouse_id', $invoice->warehouse_id);
                                                $set('reference', $invoice->number);
                                                $set('tax_inclusive', (bool) $invoice->tax_inclusive);

                                                $items = $invoice->items->map(function ($item) {
                                                    return [
                                                        'product_id' => $item->product_id,
                                                        'product_name' => $item->product?->name ?? '-',
                                                        'unit_id' => $item->unit_id,
                                                        'unit_name' => $item->unit?->name ?? '-',
                                                        'invoice_qty' => $item->quantity,
                                                        'returnable_qty' => $item->quantity, // Simplified for now
                                                        'return_qty' => 0,
                                                        'unit_price' => $item->unit_price,
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
                                            ->default(fn() => \App\Models\NumberingSetting::getNextNumber('purchase_return') ?? 'PR/00001'),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        Group::make([
                                            Select::make('supplier_id')
                                                ->relationship('supplier', 'name')
                                                ->label('Vendor')
                                                ->disabled()
                                                ->dehydrated(),
                                        ])->columnSpan(1),

                                        Group::make([
                                            Select::make('warehouse_id')
                                                ->relationship('warehouse', 'name')
                                                ->label('Gudang Asal')
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
                                        Hidden::make('product_id')->dehydrated(),
                                        Hidden::make('unit_id')->dehydrated(),

                                        TextInput::make('product_name')
                                            ->label('Produk')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpan(3),

                                        TextInput::make('invoice_qty')
                                            ->label('Qty Faktur')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(2),

                                        TextInput::make('returnable_qty')
                                            ->label('Yang Bisa Diretur')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(2),

                                        TextInput::make('return_qty')
                                            ->label('Qty Retur')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->live(onBlur: true)
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
                                            ->columnSpan(2),

                                        TextInput::make('unit_name')
                                            ->label('Satuan')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpan(1),

                                        TextInput::make('unit_price')
                                            ->label('Harga')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(2),

                                        TextInput::make('discount_percent')
                                            ->label('Diskon (%)')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(2),

                                        TextInput::make('tax_name')
                                            ->label('Pajak')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(2),

                                        Hidden::make('tax_amount')->dehydrated(),

                                        TextInput::make('total_price')
                                            ->label('Jumlah')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->columnSpan(2),
                                    ])
                                    ->columns(18)
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
                                            ->directory('purchase-return-attachments'),
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
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];
        $subTotal = 0;
        $totalTax = 0;
        $taxInclusive = (bool) $get('tax_inclusive');

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

        $set('sub_total', round($subTotal, 2));
        $set('tax_amount', round($totalTax, 2));
        $set('total_amount', round($totalAmount, 2));
    }
}
