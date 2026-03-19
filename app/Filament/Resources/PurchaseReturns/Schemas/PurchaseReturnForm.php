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
    public static function parseNumber($value): float
    {
        if (is_null($value)) return 0;
        if (is_numeric($value)) return (float) $value;
        $clean = str_replace(['.', ','], ['', '.'], $value);
        return (float) $clean;
    }

    public static function formatNumber($value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

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
                                        ...(request()->has('purchase_invoice_id')
                                            ? [
                                                Hidden::make('purchase_invoice_id')
                                                    ->default(request()->query('purchase_invoice_id'))
                                                    ->dehydrated(),
                                                TextInput::make('purchase_invoice_number')
                                                    ->label('Tagihan Pembelian')
                                                    ->default(fn() => PurchaseInvoice::find(request()->query('purchase_invoice_id'))?->number)
                                                    ->disabled()
                                                    ->dehydrated(),
                                            ]
                                            : [
                                                Select::make('purchase_invoice_id')
                                                    ->relationship('invoice', 'number')
                                                    ->label('Tagihan Pembelian')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(fn($state, Set $set, Get $get) => self::fillFromInvoice($state, $set, $get)),
                                            ]),

                                        TextInput::make('number')
                                            ->label('Nomor Retur')
                                            ->required()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->default(fn() => \App\Models\NumberingSetting::getNextNumber('purchase_return') ?? 'PR/00001'),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        Group::make(
                                            request()->has('purchase_invoice_id')
                                                ? [
                                                    Hidden::make('supplier_id')
                                                        ->default(fn() => PurchaseInvoice::find(request()->query('purchase_invoice_id'))?->supplier_id)
                                                        ->dehydrated(),
                                                    TextInput::make('supplier_name')
                                                        ->label('Vendor')
                                                        ->default(fn() => PurchaseInvoice::with('supplier')->find(request()->query('purchase_invoice_id'))?->supplier?->name)
                                                        ->disabled()
                                                        ->dehydrated(),
                                                ]
                                                : [
                                                    Select::make('supplier_id')
                                                        ->relationship('supplier', 'name', fn($query) => $query->whereIn('type', ['vendor', 'both']))
                                                        ->label('Vendor')
                                                        ->disabled()
                                                        ->dehydrated(),
                                                ]
                                        )->columnSpan(1),

                                        Group::make(
                                            request()->has('purchase_invoice_id')
                                                ? [
                                                    Hidden::make('warehouse_id')
                                                        ->default(fn() => PurchaseInvoice::find(request()->query('purchase_invoice_id'))?->warehouse_id)
                                                        ->dehydrated(),
                                                    TextInput::make('warehouse_name')
                                                        ->label('Gudang Asal')
                                                        ->default(fn() => PurchaseInvoice::with('warehouse')->find(request()->query('purchase_invoice_id'))?->warehouse?->name)
                                                        ->disabled()
                                                        ->dehydrated(),
                                                ]
                                                : [
                                                    Select::make('warehouse_id')
                                                        ->relationship('warehouse', 'name')
                                                        ->label('Gudang Asal')
                                                        ->required()
                                                        ->live()
                                                        ->dehydrated(),
                                                ]
                                        )->columnSpan(1),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        DatePicker::make('date')
                                            ->label('Tanggal Retur')
                                            ->required()
                                            ->default(now()),
                                        TextInput::make('reference')
                                            ->label('Referensi')
                                            ->default(fn() => PurchaseInvoice::find(request()->query('purchase_invoice_id'))?->number),
                                    ]),
                            ]),

                        Section::make('Item Retur')
                            ->schema([
                                Toggle::make('tax_inclusive')
                                    ->label('Harga termasuk pajak')
                                    ->inline(false)
                                    ->default(fn() => (bool) (PurchaseInvoice::find(request()->query('purchase_invoice_id'))?->tax_inclusive ?? false))
                                    ->live()
                                    ->disabled()
                                    ->dehydrated()
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set)),

                                                     Group::make()
                                                        ->schema([
                                                            Repeater::make('items')
                                                                ->relationship()
                                                                ->default(fn() => self::getItemsFromInvoiceRecord(request()->query('purchase_invoice_id')))
                                                                ->deletable()
                                                                ->reorderable(false)
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
                                                                                 ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                                                                 ->disabled()
                                                                                 ->dehydrated()
                                                                                 ->columnSpan(2),

                                                                             TextInput::make('returnable_qty')
                                                                                 ->label('Bisa Diretur')
                                                                                 ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                                                                 ->disabled()
                                                                                 ->dehydrated()
                                                                                 ->columnSpan(2),

                                                                             TextInput::make('return_qty')
                                                                                 ->label('Qty Retur')
                                                                                 ->placeholder('0')
                                                                                 ->default(0)
                                                                                 ->required()
                                                                                 ->live()
                                                                                 ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                                                     $stateNum = self::parseNumber($state);
                                                                                     $maxNum = self::parseNumber($get('returnable_qty'));
                                                                                     if ($stateNum > $maxNum) {
                                                                                         $set('return_qty', self::formatNumber($maxNum));
                                                                                     } elseif ($stateNum < 0) {
                                                                                         $set('return_qty', 0);
                                                                                     }
                                                                                     self::calculateLineTotal($get, $set);
                                                                                 })
                                                                                 ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                                                                 ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"])
                                                                                 ->suffixAction(function (Get $get, $livewire) {
                                                                                     $productId = $get('product_id');
                                                                                     $warehouseId = $get('../../warehouse_id') ?? $livewire->data['warehouse_id'] ?? null;
                                                                                     if ($productId && $warehouseId) {
                                                                                         $stock = \App\Models\Stock::where('product_id', $productId)
                                                                                             ->where('warehouse_id', $warehouseId)
                                                                                             ->value('quantity') ?? 0;
                                                                                         return \Filament\Actions\Action::make('stock')
                                                                                             ->label(self::formatNumber($stock))
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

                                                                             TextInput::make('unit_price')
                                                                                          ->label('Harga')
                                                                                          ->readOnly()
                                                                                          ->live()
                                                                                          ->dehydrated()
                                                                                          ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                                                                          ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"])
                                                                                          ->columnSpan(3),

                                                                             TextInput::make('discount_percent')
                                                                                 ->label('Diskon (%)')
                                                                                 ->readOnly()
                                                                                 ->live()
                                                                                 ->dehydrated()
                                                                                 ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                                                                 ->columnSpan(3),

                                                                              Select::make('tax_name')
                                                                                  ->label('Pajak')
                                                                                  ->options(function () {
                                                                                      $taxes = \App\Models\Tax::pluck('name', 'name')->toArray();
                                                                                      return ['Bebas Pajak' => '...'] + $taxes;
                                                                                  })
                                                                                  ->default('Bebas Pajak')
                                                                                  ->selectablePlaceholder(false)
                                                                                  ->live()
                                                                                  ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateLineTotal($get, $set))
                                                                                  ->columnSpan(3),

                                                                             Hidden::make('tax_amount')->dehydrated(),

                                                                             TextInput::make('total_price')
                                                                                          ->label('Jumlah')
                                                                                          ->readOnly()
                                                                                          ->dehydrated()
                                                                                          ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                                                                          ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"])
                                                                                          ->columnSpan(3),
                                                                         ]),
                                                                ])
                                                                ->columnSpanFull()
                                                                ->live()
                                                                ->addable(false)
                                                                ->deletable()
                                                                ->reorderable(false)
                                                                 ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($get, $set)),
                                                         ])->extraAttributes(['class' => 'w-full border rounded-xl bg-gray-50/50 dark:bg-white/5']),
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
                                            ->readOnly()
                                            ->dehydrated()
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"]),
                                        TextInput::make('tax_amount')
                                            ->label('Total Pajak')
                                            ->readOnly()
                                            ->dehydrated()
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"]),
                                        TextInput::make('total_amount')
                                            ->label('Total Retur')
                                            ->readOnly()
                                            ->dehydrated()
                                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : $state)
                                            ->extraAttributes(['class' => 'font-bold text-lg', 'x-mask:dynamic' => "\$money(\$input, ',', '.', 0)"]),
                                    ])->columnSpan(1),
                            ]),
                    ])
                    ->columnSpan(['lg' => 3]),
            ])
            ->columns(3);
    }

    public static function getItemsFromInvoiceRecord($invoiceId): array
    {
        if (!$invoiceId) return [];

        $invoice = PurchaseInvoice::with(['items.product', 'items.unit'])->find($invoiceId);
        if (!$invoice) return [];

        return $invoice->items()->get()->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? '-',
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name ?? '-',
                'invoice_qty' => self::formatNumber($item->quantity),
                'returnable_qty' => self::formatNumber($item->quantity),
                'return_qty' => 0,
                'unit_price' => self::formatNumber($item->unit_price),
                'discount_percent' => self::formatNumber($item->discount_percent),
                'tax_name' => $item->tax_name,
                'tax_amount' => 0,
                'total_price' => 0,
            ];
        })->toArray();
    }

    public static function fillFromInvoice($state, Set $set, Get $get): void
    {
        if (!$state)
            return;

        $invoice = PurchaseInvoice::with(['supplier', 'warehouse', 'items.product', 'items.unit'])->find($state);
        if (!$invoice)
            return;

        $set('supplier_id', $invoice->supplier_id);
        $set('warehouse_id', $invoice->warehouse_id);
        $set('reference', $invoice->number);
        $set('tax_inclusive', (bool) $invoice->tax_inclusive);

        $items = $invoice->items()->get()->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? '-',
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name ?? '-',
                'invoice_qty' => self::formatNumber($item->quantity),
                'returnable_qty' => self::formatNumber($item->quantity),
                'return_qty' => 0,
                'unit_price' => self::formatNumber($item->unit_price),
                'discount_percent' => self::formatNumber($item->discount_percent),
                'tax_name' => $item->tax_name,
                'tax_amount' => 0,
                'total_price' => 0,
            ];
        })->toArray();

        $set('items', $items);
        self::updateTotals($get, $set);
    }

    public static function calculateLineTotal(Get $get, Set $set): void
    {
        $qty = self::parseNumber($get('return_qty'));
        $price = self::parseNumber($get('unit_price'));
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

        $set('tax_amount', self::formatNumber($taxAmount));
        $set('total_price', self::formatNumber($totalPrice));

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
            $qty = self::parseNumber($item['return_qty'] ?? 0);
            $price = self::parseNumber($item['unit_price'] ?? 0);
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

            $totalTax += self::parseNumber($item['tax_amount'] ?? 0);
        }

        $totalAmount = $subTotal + $totalTax;

        $set($prefix . 'sub_total', self::formatNumber($subTotal));
        $set($prefix . 'tax_amount', self::formatNumber($totalTax));
        $set($prefix . 'total_amount', self::formatNumber($totalAmount));
    }
}
