<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?int $navigationSort = 6;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|null $navigationLabel = 'Produk';
    protected static ?string $modelLabel = 'Produk';
    protected static ?string $pluralModelLabel = 'Produk';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static bool $shouldRegisterNavigation = true;

    protected static function parseNumber($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            // Replace comma with dot and remove any other non-numeric chars except dot
            $sanitized = str_replace(',', '.', $value);
            return (float) filter_var($sanitized, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        }

        return (float) ($value ?? 0);
    }


    public static function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->columns(12)
            ->schema([
                // Tipe Produk (Top)
                Section::make('Tipe Produk')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Select::make('type')
                            ->label('Jenis Produk')
                            ->options([
                                'standard' => 'Produk Standard (Stok Fisik)',
                                'variant' => 'Produk Varian',
                                'manufacturing' => 'Manufaktur (Produksi)',
                                'bundle' => 'Paket / Bundle',
                                'fixed_asset' => 'Aset Tetap',
                            ])
                            ->default('standard')
                            ->required()
                            ->disabled(fn(string $operation) => $operation === 'edit' || request()->has('type'))
                            ->dehydrated()
                            ->live(),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Produk tidak aktif tidak akan muncul di transaksi'),
                        Toggle::make('show_in_products')
                            ->label('Aktifkan di Produk')
                            ->helperText('Jika diaktifkan, aset tetap ini akan muncul di daftar produk.')
                            ->visible(fn(Get $get) => $get('type') === 'fixed_asset'),
                    ])
                    ->columnSpan(6),

                // Media (Below Type)
                Section::make('Media')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Gambar Produk')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpan(6),

                // Informasi Dasar (Below Media)
                Section::make('Informasi Dasar')
                    ->icon('heroicon-o-information-circle')
                    ->columns(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        TextInput::make('sku')
                            ->label('Kode / SKU')
                            ->default(function () {
                                $lastSku = Product::where('sku', 'like', 'SKU/%')
                                    ->orderByRaw('CAST(SUBSTRING(sku, 5) AS UNSIGNED) DESC')
                                    ->first()?->sku;

                                $nextId = 1;
                                if ($lastSku && preg_match('/SKU\/(\d+)/', $lastSku, $matches)) {
                                    $nextId = (int) $matches[1] + 1;
                                } else {
                                    $nextId = (Product::max('id') ?? 0) + 1;
                                }

                                return 'SKU/' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
                            })
                            ->unique(Product::class, 'sku', ignoreRecord: true)
                            ->readOnly()
                            ->dehydrated(),

                        Hidden::make('cost_of_goods'),

                        // Replaced Barcode with Category
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                            ]),

                        // Replaced Slug with Unit
                        Select::make('unit_id')
                            ->label('Satuan')
                            ->relationship('unit', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                            ]),

                        // Hidden slug field preserved just in case
                        TextInput::make('slug')
                            ->hidden()
                            ->dehydrated(true),

                        TextInput::make('cost_of_goods')
                            ->hidden()
                            ->dehydrated(true),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // Standard Product - Used in Manufacturing (In Form)
                Section::make('Produk ini bahan baku dari')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('form_used_in_manufacturing_list')
                            ->label('')
                            ->content(function (?Model $record) {
                                if (!$record || !$record->exists) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500 italic">Simpan produk terlebih dahulu untuk melihat penggunaan</p>');
                                }

                                $manufacturingProducts = $record->usedInManufacturing()->get();

                                if ($manufacturingProducts->isEmpty()) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500">Produk ini belum digunakan dalam produk manufaktur manapun</p>');
                                }

                                $html = '<div class="space-y-2">';
                                foreach ($manufacturingProducts as $product) {
                                    $quantity = $product->pivot->quantity ?? 0;
                                    $url = Pages\EditProduct::getUrl(['record' => $product->id]);
                                    $html .= sprintf(
                                        '<div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                                            <div class="flex flex-col">
                                                <a href="%s" class="text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                                    %s
                                                </a>
                                                <span class="text-xs text-gray-500">%s</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">%s</span>
                                                <span class="text-xs text-gray-500 ml-1">%s</span>
                                            </div>
                                        </div>',
                                        $url,
                                        e($product->name),
                                        e($product->sku),
                                        number_format($quantity, 2),
                                        e($record->unit?->name ?? 'unit')
                                    );
                                }
                                $html .= '</div>';
                                return new \Illuminate\Support\HtmlString($html);
                            })
                    ])
                    ->columnSpanFull()
                    ->visible(fn(Get $get, ?Model $record) => in_array($get('type'), ['standard', 'service']))
                    ->collapsible(),

                // BOTTOM SECTIONS: Inventory & Pricing (Span 12 / Row 3)


                // Pembelian (Left 6) - Only for products that can be purchased
                Section::make('Pembelian')
                    ->icon('heroicon-o-shopping-cart')
                    ->iconColor('warning')
                    ->schema([
                        Toggle::make('can_be_purchased')
                            ->label('Saya membeli item ini')
                            ->onColor('warning')
                            ->default(fn(Get $get) => !in_array($get('type'), ['bundle', 'service']))
                            ->live(),

                        Toggle::make('show_tax_settings_purchase')
                            ->label('Tampilkan pengaturan akun dan pajak')
                            ->onColor('warning')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if ($state && !$get('purchase_account_id')) {
                                    $set('purchase_account_id', 219);
                                }
                            })
                            ->dehydrated(false)
                            ->visible(fn(Get $get) => $get('can_be_purchased')),

                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('buy_price')
                                    ->label('Harga Beli')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->columnSpanFull(),

                                Select::make('purchase_account_id')
                                    ->label('Akun Pembelian')
                                    ->relationship('purchaseAccount', 'name', fn(Builder $query) => $query->orderBy('code'))
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                                    ->searchable()
                                    ->preload()
                                    ->default(219)
                                    ->visible(fn(Get $get) => $get('show_tax_settings_purchase')),

                                Select::make('purchase_tax_id')
                                    ->label('Pajak Beli')
                                    ->relationship('purchaseTax', 'name')
                                    ->placeholder('Pilih Pajak')
                                    ->nullable()
                                    ->live()
                                    ->visible(fn(Get $get) => $get('show_tax_settings_purchase')),

                                Toggle::make('purchase_price_includes_tax')
                                    ->label('Harga termasuk pajak')
                                    ->default(false)
                                    ->live()
                                    ->columnSpanFull()
                                    ->visible(fn(Get $get) => $get('show_tax_settings_purchase')),

                                \Filament\Forms\Components\Placeholder::make('purchase_tax_calculation')
                                    ->hiddenLabel()
                                    ->content(function (Get $get) {
                                        $price = (float) $get('buy_price');
                                        $taxId = $get('purchase_tax_id');
                                        $includesTax = (bool) $get('purchase_price_includes_tax');

                                        $taxRate = 0;
                                        if ($taxId) {
                                            $tax = \App\Models\Tax::find($taxId);
                                            $taxRate = $tax?->rate ?? 0;
                                        }

                                        if ($taxRate <= 0 || $price <= 0)
                                            return new \Illuminate\Support\HtmlString("<div style='padding: 0.75rem; text-align: center; color: #9ca3af; font-size: 0.875rem; background-color: #f9fafb; border-radius: 0.5rem; border: 1px dashed #e5e7eb;' class='dark:bg-gray-800 dark:border-gray-700'>Pilih pajak untuk melihat rincian</div>");

                                        if ($includesTax) {
                                            $base = $price * 100 / (100 + $taxRate);
                                            $tax = $price - $base;
                                        } else {
                                            $base = $price;
                                            $tax = $price * $taxRate / 100;
                                        }
                                        $total = $base + $tax;

                                        return new \Illuminate\Support\HtmlString(
                                            "<div style='padding: 1rem; background-color: #fffbeb; border-radius: 0.75rem; border: 1px solid #fde68a;' class='bg-gradient-to-br from-amber-50 to-orange-50 dark:from-gray-800 dark:to-gray-900 border-amber-200 dark:border-amber-800 shadow-sm'>" .
                                            "<div style='display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #fde68a;' class='border-amber-200 dark:border-amber-700'>" .
                                            "<svg style='width: 16px; height: 16px; color: #d97706;' class='w-4 h-4 text-amber-600' fill='none' stroke='currentColor' viewBox='0 0 24 24' width='16' height='16'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'></path></svg>" .
                                            "<span style='font-size: 0.875rem; font-weight: 600; color: #92400e;' class='text-sm font-semibold text-amber-800 dark:text-amber-300'>Rincian Pajak Pembelian</span>" .
                                            "</div>" .
                                            "<div class='space-y-2'>" .
                                            "<div style='display: flex; justify-content: space-between; align-items: center;'><span class='text-sm text-gray-600 dark:text-gray-400'>DPP (Dasar Pengenaan Pajak)</span><span class='text-sm font-semibold text-gray-800 dark:text-gray-200'>Rp " . number_format($base, 0, ',', '.') . "</span></div>" .
                                            "<div style='display: flex; justify-content: space-between; align-items: center;'><span class='text-sm text-gray-600 dark:text-gray-400'>Pajak ({$taxRate}%)</span><span class='text-sm font-semibold text-amber-600'>Rp " . number_format($tax, 0, ',', '.') . "</span></div>" .
                                            "<div style='display: flex; justify-content: space-between; align-items: center; padding-top: 0.5rem; margin-top: 0.5rem; border-top: 2px solid #fcd34d;' class='border-amber-300 dark:border-amber-600'><span class='text-sm font-bold text-gray-800 dark:text-gray-200'>Total</span><span class='text-base font-bold text-amber-700 dark:text-amber-400'>Rp " . number_format($total, 0, ',', '.') . "</span></div>" .
                                            "</div>" .
                                            "</div>"
                                        );
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn(Get $get) => $get('can_be_purchased') && $get('type') !== 'variant'),
                    ])
                    ->columnSpan(['default' => 12, 'md' => 6])
                    ->visible(fn(Get $get) => !in_array($get('type'), ['bundle', 'service'])),

                // Penjualan (Right 6)
                Section::make('Penjualan')
                    ->icon('heroicon-o-banknotes')
                    ->iconColor('success')
                    ->schema([
                        Toggle::make('can_be_sold')
                            ->label('Saya menjual item ini')
                            ->onColor('success')
                            ->default(true)
                            ->live(),

                        Toggle::make('show_tax_settings_sales')
                            ->label('Tampilkan pengaturan akun dan pajak')
                            ->onColor('success')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if ($state && !$get('sales_account_id')) {
                                    $set('sales_account_id', 215);
                                }
                            })
                            ->dehydrated(false)
                            ->visible(fn(Get $get) => $get('can_be_sold')),

                        TextInput::make('sell_price')
                            ->label('Harga Jual')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->required()
                            ->columnSpanFull()
                            ->visible(fn(Get $get) => $get('can_be_sold') && $get('type') !== 'variant'),

                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                Select::make('sales_account_id')
                                    ->label('Akun Penjualan')
                                    ->relationship('salesAccount', 'name', fn(Builder $query) => $query->orderBy('code'))
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                                    ->searchable()
                                    ->preload()
                                    ->default(215)
                                    ->visible(fn(Get $get) => $get('show_tax_settings_sales')),

                                Select::make('sales_tax_id')
                                    ->label('Pajak Jual')
                                    ->relationship('salesTax', 'name')
                                    ->placeholder('Pilih Pajak')
                                    ->nullable()
                                    ->live()
                                    ->visible(fn(Get $get) => $get('show_tax_settings_sales')),

                                Toggle::make('sales_price_includes_tax')
                                    ->label('Harga termasuk pajak')
                                    ->default(false)
                                    ->live()
                                    ->columnSpanFull()
                                    ->visible(fn(Get $get) => $get('show_tax_settings_sales')),

                                \Filament\Forms\Components\Placeholder::make('sales_tax_calculation')
                                    ->hiddenLabel()
                                    ->content(function (Get $get) {
                                        $price = (float) $get('sell_price');
                                        $taxId = $get('sales_tax_id');
                                        $includesTax = (bool) $get('sales_price_includes_tax');

                                        $taxRate = 0;
                                        if ($taxId) {
                                            $tax = \App\Models\Tax::find($taxId);
                                            $taxRate = $tax?->rate ?? 0;
                                        }

                                        if ($taxRate <= 0 || $price <= 0)
                                            return new \Illuminate\Support\HtmlString("<div style='padding: 0.75rem; text-align: center; color: #9ca3af; font-size: 0.875rem; background-color: #f9fafb; border-radius: 0.5rem; border: 1px dashed #e5e7eb;' class='dark:bg-gray-800 dark:border-gray-700'>Pilih pajak untuk melihat rincian</div>");

                                        if ($includesTax) {
                                            $base = $price * 100 / (100 + $taxRate);
                                            $tax = $price - $base;
                                        } else {
                                            $base = $price;
                                            $tax = $price * $taxRate / 100;
                                        }
                                        $total = $base + $tax;

                                        return new \Illuminate\Support\HtmlString(
                                            "<div style='padding: 1rem; background-color: #ecfdf5; border-radius: 0.75rem; border: 1px solid #a7f3d0;' class='bg-gradient-to-br from-emerald-50 to-green-50 dark:from-gray-800 dark:to-gray-900 border-emerald-200 dark:border-emerald-800 shadow-sm'>" .
                                            "<div style='display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #a7f3d0;' class='border-emerald-200 dark:border-emerald-700'>" .
                                            "<svg style='width: 16px; height: 16px; color: #059669;' class='w-4 h-4 text-emerald-600' fill='none' stroke='currentColor' viewBox='0 0 24 24' width='16' height='16'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'></path></svg>" .
                                            "<span style='font-size: 0.875rem; font-weight: 600; color: #065f46;' class='text-sm font-semibold text-emerald-800 dark:text-emerald-300'>Rincian Pajak Penjualan</span>" .
                                            "</div>" .
                                            "<div class='space-y-2'>" .
                                            "<div style='display: flex; justify-content: space-between; align-items: center;'><span class='text-sm text-gray-600 dark:text-gray-400'>DPP (Dasar Pengenaan Pajak)</span><span class='text-sm font-semibold text-gray-800 dark:text-gray-200'>Rp " . number_format($base, 0, ',', '.') . "</span></div>" .
                                            "<div style='display: flex; justify-content: space-between; align-items: center;'><span class='text-sm text-gray-600 dark:text-gray-400'>Pajak ({$taxRate}%)</span><span class='text-sm font-semibold text-emerald-600'>Rp " . number_format($tax, 0, ',', '.') . "</span></div>" .
                                            "<div style='display: flex; justify-content: space-between; align-items: center; padding-top: 0.5rem; margin-top: 0.5rem; border-top: 2px solid #6ee7b7;' class='border-emerald-300 dark:border-emerald-600'><span class='text-sm font-bold text-gray-800 dark:text-gray-200'>Total</span><span class='text-base font-bold text-emerald-700 dark:text-emerald-400'>Rp " . number_format($total, 0, ',', '.') . "</span></div>" .
                                            "</div>" .
                                            "</div>"
                                        );
                                    })
                                    ->columnSpanFull()
                                    ->visible(fn(Get $get) => $get('can_be_sold') && $get('type') !== 'variant' && $get('show_tax_settings_sales')),
                            ])
                            ->visible(fn(Get $get) => $get('can_be_sold') && $get('type') !== 'variant'),

                        Toggle::make('show_wholesale_prices')
                            ->label('Tampilkan Harga Grosir')
                            ->onColor('success')
                            ->default(false)
                            ->live()
                            ->dehydrated(false)
                            ->visible(fn(Get $get) => $get('can_be_sold')),

                        Repeater::make('wholesale_prices')
                            ->label('Harga Grosir')
                            ->schema([
                                TextInput::make('min_quantity')
                                    ->label('Kuantitas Minimal')
                                    ->numeric()
                                    ->step('any')
                                    ->required(),
                                TextInput::make('price')
                                    ->label('Harga')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('+ Tambah Harga Grosir')
                            ->visible(fn(Get $get) => $get('can_be_sold') && $get('type') !== 'variant' && $get('show_wholesale_prices'))
                            ->columnSpanFull()
                            ->collapsible()
                            ->collapsed(),
                    ])
                    ->columnSpan(fn(Get $get) => in_array($get('type'), ['bundle', 'service']) ? 'full' : ['default' => 12, 'md' => 6]),

                // Manufacturing - Bill of Materials
                Section::make('Produk manufaktur terdiri dari')
                    ->icon('heroicon-o-beaker')
                    ->description('Daftar bahan baku yang diperlukan untuk memproduksi produk ini')
                    ->schema([
                        Repeater::make('productMaterials')
                            ->relationship('productMaterials')
                            ->label('')
                            ->schema([
                                Select::make('material_id')
                                    ->label('Produk')
                                    ->options(Product::active()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?int $state) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('unit_name', $product->unit?->name ?? 'Pcs');
                                                $set('unit_price', $product->cost_of_goods ?? 0);
                                            }
                                        }
                                    })
                                    ->columnSpan(4),
                                TextInput::make('quantity')
                                    ->label('Kuantitas')
                                    ->afterStateHydrated(fn(TextInput $component, $state) => $component->state((float) $state))
                                    ->inputMode('decimal')
                                    ->regex('/^[0-9]*[.,]?[0-9]*$/')
                                    ->default(0)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->columnSpan(2),
                                TextInput::make('unit_name')
                                    ->label('Satuan')
                                    ->afterStateHydrated(function (TextInput $component, Get $get) {
                                        $materialId = $get('material_id');
                                        if ($materialId) {
                                            $product = Product::find($materialId);
                                            $component->state($product?->unit?->name ?? 'Pcs');
                                        }
                                    })
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default('Pcs')
                                    ->columnSpan(2),
                                TextInput::make('unit_price')
                                    ->label('Harga Satuan')
                                    ->afterStateHydrated(function (TextInput $component, Get $get) {
                                        $materialId = $get('material_id');
                                        if ($materialId) {
                                            $product = Product::find($materialId);
                                            $component->state($product?->cost_of_goods ?? 0);
                                        }
                                    })
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(0)
                                    ->columnSpan(2),
                                \Filament\Forms\Components\Placeholder::make('subtotal')
                                    ->label('Total Harga')
                                    ->content(function (Get $get) {
                                        $qty = static::parseNumber($get('quantity'));
                                        $price = static::parseNumber($get('unit_price'));
                                        return "Rp " . number_format($qty * $price, 2, ',', '.');
                                    })
                                    ->columnSpan(3),
                            ])
                            ->columns(13)
                            ->addActionLabel('+ Tambah baris')
                            ->defaultItems(1)
                            ->reorderable()
                            ->cloneable(),
                    ])
                    ->columnSpanFull()
                    ->visible(fn(Get $get) => $get('type') === 'manufacturing'),

                // Manufacturing - Production Costs
                Section::make('Biaya produksi terdiri dari')
                    ->icon('heroicon-o-banknotes')
                    ->description('Biaya overhead seperti tenaga kerja, listrik, dll')
                    ->schema([
                        Repeater::make('productionCosts')
                            ->relationship('productionCosts')
                            ->label('')
                            ->schema([
                                Select::make('account_id')
                                    ->label('Akun')
                                    ->relationship('account', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(4),
                                TextInput::make('unit_amount')
                                    ->label('Per Pcs')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $set('amount', (float) ($get('unit_amount') ?? 0) * (float) ($get('multiplier') ?? 1));
                                    })
                                    ->columnSpan(2),
                                TextInput::make('multiplier')
                                    ->label('Pengali')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $set('amount', (float) ($get('unit_amount') ?? 0) * (float) ($get('multiplier') ?? 1));
                                    })
                                    ->columnSpan(2),
                                TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->required()
                                    ->columnSpan(3),
                            ])
                            ->columns(11)
                            ->addActionLabel('+ Tambah baris')
                            ->defaultItems(1)
                            ->reorderable(),

                        \Filament\Forms\Components\Placeholder::make('total_biaya')
                            ->label('')
                            ->content(function (Get $get, Set $set) {
                                $materials = $get('productMaterials') ?? [];
                                $costs = $get('productionCosts') ?? [];

                                $materialTotal = 0;
                                $totalQty = 0;
                                foreach ($materials as $material) {
                                    $qty = static::parseNumber($material['quantity'] ?? 0);
                                    $price = static::parseNumber($material['unit_price'] ?? 0);
                                    $materialTotal += $qty * $price;
                                    $totalQty += $qty;
                                }

                                $costTotal = 0;
                                foreach ($costs as $cost) {
                                    $costTotal += static::parseNumber($cost['amount'] ?? 0);
                                }

                                $total = $materialTotal + $costTotal;

                                // Sync with hidden cost_of_goods field
                                $set('cost_of_goods', $total);

                                return new \Illuminate\Support\HtmlString(
                                    "<div style='position: relative; overflow: hidden; border-radius: 16px; background: linear-gradient(135deg, #64748b 0%, #7c3aed 50%, #a855f7 100%); padding: 32px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);'>" .
                                    "<div style='position: absolute; top: 0; right: 0; width: 160px; height: 160px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; margin-right: -80px; margin-top: -80px;'></div>" .
                                    "<div style='position: absolute; bottom: 0; left: 0; width: 128px; height: 128px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; margin-left: -64px; margin-bottom: -64px;'></div>" .
                                    "<div style='position: relative;'>" .

                                    "<div style='margin-bottom: 24px;'>" .
                                    "<h3 style='font-size: 20px; font-weight: 700; color: white; letter-spacing: -0.025em;'>Ringkasan Biaya Produksi</h3>" .
                                    "</div>" .

                                    "<div style='margin-bottom: 24px;'>" .
                                    "<div style='display: flex; justify-content: space-between; align-items: center; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(8px); border-radius: 12px; padding: 16px 24px; margin-bottom: 16px; transition: all 0.2s;'>" .
                                    "<span style='color: rgba(255, 255, 255, 0.9); font-weight: 500; font-size: 16px;'>Total Kuantitas Bahan</span>" .
                                    "<span style='color: white; font-weight: 700; font-size: 18px;'>" . number_format($totalQty, 2, ',', '.') . "</span>" .
                                    "</div>" .
                                    "<div style='display: flex; justify-content: space-between; align-items: center; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(8px); border-radius: 12px; padding: 16px 24px; margin-bottom: 16px; transition: all 0.2s;'>" .
                                    "<span style='color: rgba(255, 255, 255, 0.9); font-weight: 500; font-size: 16px;'>Total Biaya Bahan</span>" .
                                    "<span style='color: white; font-weight: 700; font-size: 18px;'>Rp " . number_format($materialTotal, 2, ',', '.') . "</span>" .
                                    "</div>" .
                                    "<div style='display: flex; justify-content: space-between; align-items: center; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(8px); border-radius: 12px; padding: 16px 24px; transition: all 0.2s;'>" .
                                    "<span style='color: rgba(255, 255, 255, 0.9); font-weight: 500; font-size: 16px;'>Biaya Produksi</span>" .
                                    "<span style='color: white; font-weight: 700; font-size: 18px;'>Rp " . number_format($costTotal, 2, ',', '.') . "</span>" .
                                    "</div>" .
                                    "</div>" .

                                    "<div style='border-top: 2px solid rgba(255, 255, 255, 0.4); padding-top: 20px;'>" .
                                    "<div style='display: flex; justify-content: space-between; align-items: center;'>" .
                                    "<span style='color: white; font-size: 20px; font-weight: 700;'>Total Biaya</span>" .
                                    "<span style='color: white; font-size: 30px; font-weight: 900; letter-spacing: -0.025em;'>Rp " . number_format($total, 2, ',', '.') . "</span>" .
                                    "</div>" .
                                    "</div>" .

                                    "</div>" .
                                    "</div>"
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->visible(fn(Get $get) => $get('type') === 'manufacturing'),

                // Standard Product - Used in Manufacturing
                Section::make('Produk ini bahan baku dari')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->description('Daftar produk manufaktur yang menggunakan produk ini sebagai bahan baku')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('used_in_manufacturing_list')
                            ->label('')
                            ->content(function (?Model $record) {
                                if (!$record || !$record->exists) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500">Simpan produk terlebih dahulu untuk melihat penggunaan</p>');
                                }

                                $manufacturingProducts = $record->usedInManufacturing()->get();

                                if ($manufacturingProducts->isEmpty()) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500">Produk ini belum digunakan dalam produk manufaktur manapun</p>');
                                }

                                $html = '<div class="space-y-2">';
                                foreach ($manufacturingProducts as $product) {
                                    $quantity = $product->pivot->quantity ?? 0;
                                    $url = route('filament.admin.resources.products.edit', ['record' => $product->id]);
                                    $html .= sprintf(
                                        '<div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                            <a href="%s" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 flex-1">
                                                %s (%s)
                                            </a>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-4">Qty: %s %s</span>
                                        </div>',
                                        $url,
                                        e($product->name),
                                        e($product->sku),
                                        number_format($quantity, 2),
                                        e($record->unit?->name ?? 'unit')
                                    );
                                }
                                $html .= '</div>';

                                $html .= sprintf(
                                    '<p class="text-xs text-gray-500 dark:text-gray-400 mt-4"><a href="#" class="text-primary-600 hover:underline">Tampilkan Lebih Sedikit</a></p>'
                                );

                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->visible(fn(Get $get) => in_array($get('type'), ['standard', 'service']))
                    ->collapsible()
                    ->collapsed(),

                // Bundle - Package Items
                Section::make('Paket Produk terdiri dari')
                    ->icon('heroicon-o-gift')
                    ->description('Daftar produk yang termasuk dalam paket ini')
                    ->schema([
                        Repeater::make('productBundles')
                            ->relationship('productBundles')
                            ->label('')
                            ->schema([
                                Select::make('item_id')
                                    ->label('Produk')
                                    ->options(Product::active()->whereIn('type', ['standard', 'service'])->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?int $state) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('unit_price', $product->sell_price ?? 0);
                                            }
                                        }
                                    })
                                    ->columnSpan(5),
                                TextInput::make('quantity')
                                    ->label('Kuantitas')
                                    ->afterStateHydrated(fn(TextInput $component, $state) => $component->state((float) $state))
                                    ->numeric()
                                    ->step('any')
                                    ->default(0)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->columnSpan(3),
                                TextInput::make('unit_price')
                                    ->label('Harga')
                                    ->afterStateHydrated(function (TextInput $component, Get $get) {
                                        $itemId = $get('item_id');
                                        if ($itemId) {
                                            $product = Product::find($itemId);
                                            $component->state($product?->sell_price ?? 0);
                                        }
                                    })
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(0)
                                    ->columnSpan(3),
                            ])
                            ->columns(11)
                            ->addActionLabel('+ Tambah baris')
                            ->defaultItems(1)
                            ->reorderable()
                            ->cloneable(),

                        \Filament\Forms\Components\Placeholder::make('bundle_total')
                            ->label('')
                            ->content(function (Get $get, Set $set) {
                                $items = $get('productBundles') ?? [];

                                $total = 0;
                                foreach ($items as $item) {
                                    $qty = static::parseNumber($item['quantity'] ?? 0);
                                    $price = static::parseNumber($item['unit_price'] ?? 0);
                                    $total += $qty * $price;
                                }

                                // Sync HPP for bundles
                                $set('cost_of_goods', $total);

                                return new \Illuminate\Support\HtmlString(
                                    "<div class='flex justify-end items-center gap-4 py-2 px-4 bg-gray-100 dark:bg-gray-800 rounded-lg'>" .
                                    "<span class='text-sm font-medium text-gray-600 dark:text-gray-400'>Biaya total</span>" .
                                    "<span class='text-lg font-bold text-gray-900 dark:text-white'>Rp " . number_format($total, 0, ',', '.') . "</span>" .
                                    "</div>"
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->visible(fn(Get $get) => $get('type') === 'bundle'),

                // Variant - Product Variants Section
                Section::make('Produk Varian')
                    ->icon('heroicon-o-squares-2x2')
                    ->description('Tentukan varian produk seperti Warna, Ukuran, dll')
                    ->schema([
                        Repeater::make('variantAttributes')
                            ->label('Atribut Varian')
                            ->schema([
                                Select::make('attribute_id')
                                    ->label('Varian')
                                    ->options(\App\Models\VariantAttribute::pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Nama Varian')
                                            ->required()
                                            ->placeholder('contoh: Warna, Ukuran'),
                                        \Filament\Forms\Components\TagsInput::make('options')
                                            ->label('Opsi')
                                            ->placeholder('Ketik opsi lalu tekan Enter')
                                            ->helperText('contoh: Merah, Biru, Hijau'),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $attr = \App\Models\VariantAttribute::create($data);
                                        return $attr->id;
                                    })
                                    ->columnSpan(4),
                                Select::make('options')
                                    ->label('Opsi')
                                    ->multiple()
                                    ->options(function (Get $get) {
                                        $attrId = $get('attribute_id');
                                        if (!$attrId)
                                            return [];
                                        $attr = \App\Models\VariantAttribute::find($attrId);
                                        if (!$attr || !$attr->options)
                                            return [];
                                        return collect($attr->options)->mapWithKeys(fn($opt) => [$opt => $opt])->toArray();
                                    })
                                    ->searchable()
                                    ->columnSpan(7),
                            ])
                            ->columns(11)
                            ->addActionLabel('+ Tambah baris')
                            ->defaultItems(1)
                            ->reorderable()
                            ->dehydrated(false),

                        Repeater::make('productVariants')
                            ->relationship('productVariants')
                            ->label('Daftar Varian')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Produk')
                                    ->required()
                                    ->columnSpan(3),
                                FileUpload::make('image')
                                    ->label('Gambar')
                                    ->image()
                                    ->disk('public')
                                    ->directory('variants')
                                    ->avatar()
                                    ->circleCropper()
                                    ->columnSpan(1),
                                TextInput::make('sku')
                                    ->label('Kode/SKU')
                                    ->columnSpan(2),
                                TextInput::make('buy_price')
                                    ->label('Harga Beli')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->columnSpan(2),
                                TextInput::make('sell_price')
                                    ->label('Harga Jual')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->columnSpan(2),
                            ])
                            ->columns(12)
                            ->addActionLabel('+ Tambah varian')
                            ->defaultItems(0)
                            ->reorderable()
                            ->cloneable(),
                    ])
                    ->columnSpanFull()
                    ->visible(fn(Get $get) => $get('type') === 'variant'),

                // Inventory (Bottom)
                Section::make('Inventori')
                    ->icon('heroicon-o-archive-box')
                    ->iconColor('primary')
                    ->schema([
                        Toggle::make('track_inventory')
                            ->label('Saya melacak inventori item ini')
                            ->onColor('primary')
                            ->default(true)
                            ->live(),

                        \Filament\Schemas\Components\Grid::make(3)
                            ->schema([
                                Select::make('inventory_account_id')
                                    ->label('Akun Persediaan')
                                    ->relationship('inventoryAccount', 'name', fn(Builder $query) => $query->orderBy('code'))
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                                    ->searchable()
                                    ->preload()
                                    ->default(163)
                                    ->columnSpanFull(),

                                TextInput::make('min_stock')
                                    ->label('Stok Minimal')
                                    ->numeric()
                                    ->default(0),

                                TextInput::make('stock')
                                    ->label('Stok Awal')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(fn(string $operation) => $operation !== 'create')
                                    ->dehydrated(true)
                                    ->helperText(fn(string $operation) => $operation === 'create' ? 'Initial stock quantity' : 'Stock is updated via transactions'),

                                Select::make('initial_warehouse_id')
                                    ->label('Gudang Awal')
                                    ->options(['' => 'Unassigned'] + \App\Models\Warehouse::pluck('name', 'id')->toArray())
                                    ->default('')
                                    // ->required(fn(Get $get) => $get('stock') > 0)
                                    ->visible(fn(string $operation) => $operation === 'create'),
                            ])
                            ->visible(fn(Get $get) => $get('track_inventory')),
                    ])
                    ->columnSpanFull()
                    ->visible(fn(Get $get) => in_array($get('type'), ['standard', 'manufacturing', 'variant'])),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(2)
                    ->schema([
                        // Satuan Section
                        Section::make('Satuan')
                            ->schema([
                                TextEntry::make('unit.name')
                                    ->label('Satuan Dasar'),

                                RepeatableEntry::make('productUnits')
                                    ->label('Konversi Satuan')
                                    ->schema([
                                        TextEntry::make('unit.name')
                                            ->label('Satuan'),
                                        TextEntry::make('value')
                                            ->label('Kuantitas')
                                            ->formatStateUsing(fn($state, $record) => (float) $state . " {$record->product?->unit?->name}"),
                                        TextEntry::make('price')
                                            ->label('Harga Beli')
                                            ->money('IDR'),
                                        TextEntry::make('sell_price')
                                            ->label('Harga Jual')
                                            ->money('IDR'),
                                        IconEntry::make('is_default')
                                            ->label('Default')
                                            ->boolean()
                                            ->trueIcon('heroicon-s-star')
                                            ->falseIcon('heroicon-o-star')
                                            ->color('warning'),
                                    ])
                                    ->columns(5)
                                    ->grid(1)
                            ])
                            ->headerActions([
                                Action::make('change_conversion')
                                    ->label('Ubah konversi satuan')
                                    ->icon('heroicon-m-plus')
                                    ->modalHeading('Konversi Satuan')
                                    ->modalWidth('6xl')
                                    ->modalFooterActionsAlignment(\Filament\Support\Enums\Alignment::End)
                                    ->modalSubmitAction(fn($action) => $action->label('Simpan')->icon('heroicon-m-archive-box-arrow-down'))
                                    ->modalCancelAction(fn($action) => $action->label('Batal')->icon('heroicon-m-x-mark'))
                                    ->fillForm(fn($record) => [
                                        'base_buy_price' => (float) ($record->buy_price ?? 0),
                                        'base_sell_price' => (float) ($record->sell_price ?? 0),
                                        // Reverse-calculate original base-unit price from existing conversions
                                        // e.g. if 100 Pcs × Rp1.200 = Rp120.000 (original Pak price)
                                        'original_buy_price' => $record->productUnits->isNotEmpty()
                                            ? (function () use ($record) {
                                                $ref = $record->productUnits->firstWhere('is_default', true)
                                                    ?? $record->productUnits->first();
                                                return round((float) ($ref->price ?? 0) * (float) ($ref->value ?? 1), 2);
                                            })()
                                            : (float) ($record->buy_price ?? 0),
                                        'original_sell_price' => $record->productUnits->isNotEmpty()
                                            ? (function () use ($record) {
                                                $ref = $record->productUnits->firstWhere('is_default', true)
                                                    ?? $record->productUnits->first();
                                                return round((float) ($ref->sell_price ?? 0) * (float) ($ref->value ?? 1), 2);
                                            })()
                                            : (float) ($record->sell_price ?? 0),
                                        'productUnits' => $record->productUnits->isEmpty()
                                            ? [['unit_id' => null, 'value' => 1, 'price' => 0, 'sell_price' => 0, 'is_default' => false]]
                                            : $record->productUnits->map(fn($pu) => [
                                                'unit_id' => $pu->unit_id,
                                                'value' => $pu->value,
                                                'price' => $pu->price,
                                                'sell_price' => $pu->sell_price,
                                                'is_default' => (bool) $pu->is_default,
                                            ])->values()->toArray(),
                                    ])
                                    ->form([
                                        // Hidden base prices for auto-calculation
                                        \Filament\Forms\Components\Hidden::make('base_buy_price'),
                                        \Filament\Forms\Components\Hidden::make('base_sell_price'),
                                        \Filament\Forms\Components\Hidden::make('original_buy_price'),
                                        \Filament\Forms\Components\Hidden::make('original_sell_price'),

                                        Grid::make(24)
                                            ->schema([
                                                // blank to cover base_unit + eq + value cols (3+1+3=7)
                                                \Filament\Forms\Components\Placeholder::make('h_blank')
                                                    ->hiddenLabel()
                                                    ->content('')
                                                    ->columnSpan(7),
                                                \Filament\Forms\Components\Placeholder::make('h_satuan')
                                                    ->hiddenLabel()
                                                    ->content(new \Illuminate\Support\HtmlString('<span class="text-sm font-bold text-gray-800">Satuan</span>'))
                                                    ->columnSpan(7),
                                                \Filament\Forms\Components\Placeholder::make('h_beli')
                                                    ->hiddenLabel()
                                                    ->content(new \Illuminate\Support\HtmlString('<span class="text-sm font-bold text-gray-800 text-center block">Harga Beli</span>'))
                                                    ->columnSpan(4),
                                                \Filament\Forms\Components\Placeholder::make('h_jual')
                                                    ->hiddenLabel()
                                                    ->content(new \Illuminate\Support\HtmlString('<span class="text-sm font-bold text-gray-800 text-center block">Harga Jual</span>'))
                                                    ->columnSpan(4),
                                                \Filament\Forms\Components\Placeholder::make('h_default')
                                                    ->hiddenLabel()
                                                    ->content(new \Illuminate\Support\HtmlString('<span class="text-sm font-bold text-gray-800 text-center block">Default</span>'))
                                                    ->columnSpan(2),
                                            ])
                                            ->extraAttributes(['class' => 'bg-gray-50 px-3 py-2 rounded-t-xl border-b border-gray-200'])
                                            ->columnSpanFull(),

                                        Repeater::make('productUnits')
                                            ->hiddenLabel()
                                            ->schema([
                                                Grid::make(24)
                                                    ->schema([
                                                        // base_unit on the left: "Roll"
                                                        \Filament\Forms\Components\Placeholder::make('base_unit')
                                                            ->hiddenLabel()
                                                            ->content(fn($record) => $record?->unit?->name ?? '-')
                                                            ->extraAttributes(['class' => 'flex items-center font-semibold text-gray-700'])
                                                            ->columnSpan(3),

                                                        // equals sign
                                                        \Filament\Forms\Components\Placeholder::make('eq')
                                                            ->hiddenLabel()
                                                            ->content('=')
                                                            ->extraAttributes(['class' => 'flex items-center justify-center font-bold text-gray-400 text-lg'])
                                                            ->columnSpan(1),

                                                        // conversion quantity: "100"
                                                        TextInput::make('value')
                                                            ->hiddenLabel()
                                                            ->numeric()
                                                            ->default(1)
                                                            ->required()
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                                $qty = (float) $state;
                                                                if ($qty <= 0)
                                                                    return;
                                                                $baseBuy = (float) $get('../../base_buy_price');
                                                                $baseSell = (float) $get('../../base_sell_price');
                                                                $set('price', round($baseBuy / $qty, 2));
                                                                if ($baseSell > 0) {
                                                                    $set('sell_price', round($baseSell / $qty, 2));
                                                                }
                                                            })
                                                            ->columnSpan(3),

                                                        // conversion unit dropdown: "Meter"
                                                        Select::make('unit_id')
                                                            ->hiddenLabel()
                                                            ->options(\App\Models\Unit::pluck('name', 'id'))
                                                            ->searchable()
                                                            ->required()
                                                            ->placeholder('Pilih Satuan')
                                                            ->columnSpan(7),

                                                        TextInput::make('price')
                                                            ->hiddenLabel()
                                                            ->numeric()
                                                            ->default(0)
                                                            ->prefix('Rp')
                                                            ->extraInputAttributes(['class' => 'text-right'])
                                                            ->columnSpan(4),

                                                        TextInput::make('sell_price')
                                                            ->hiddenLabel()
                                                            ->numeric()
                                                            ->default(0)
                                                            ->prefix('Rp')
                                                            ->extraInputAttributes(['class' => 'text-right'])
                                                            ->columnSpan(4),

                                                        Toggle::make('is_default')
                                                            ->hiddenLabel()
                                                            ->onIcon('heroicon-m-star')
                                                            ->offIcon('heroicon-o-star')
                                                            ->onColor('warning')
                                                            ->inline(false)
                                                            ->columnSpan(2),
                                                    ]),
                                            ])
                                            ->addActionLabel('+ Tambah baris')
                                            ->defaultItems(1)
                                            ->reorderable(false)
                                            ->collapsible(false)
                                            ->deleteAction(fn($action) => $action->icon('heroicon-m-trash')->color('danger'))
                                            ->extraAttributes(['class' => 'konversi-repeater'])
                                            ->columns(1),
                                    ])
                                    ->action(function ($record, array $data) {
                                        $record->productUnits()->delete();
                                        foreach ($data['productUnits'] as $unitData) {
                                            $record->productUnits()->create($unitData);
                                        }

                                        $units = collect($data['productUnits'])->filter(
                                            fn($u) => !empty($u['unit_id'])
                                        );

                                        $defaultUnit = $units->firstWhere('is_default', true);

                                        if ($defaultUnit) {
                                            // Default unit set → use its converted price
                                            $updateData = [
                                                'buy_price' => (float) ($defaultUnit['price'] ?? 0),
                                                'cost_of_goods' => (float) ($defaultUnit['price'] ?? 0),
                                            ];
                                            if (!empty($defaultUnit['sell_price']) && (float) $defaultUnit['sell_price'] > 0) {
                                                $updateData['sell_price'] = (float) $defaultUnit['sell_price'];
                                            }
                                            $record->update($updateData);
                                        } else {
                                            // No default → restore to original base-unit price
                                            $originalBuy = (float) ($data['original_buy_price'] ?? 0);
                                            $originalSell = (float) ($data['original_sell_price'] ?? 0);
                                            $restore = [
                                                'buy_price' => $originalBuy,
                                                'cost_of_goods' => $originalBuy,
                                            ];
                                            if ($originalSell > 0) {
                                                $restore['sell_price'] = $originalSell;
                                            }
                                            $record->update($restore);
                                        }
                                    })
                                    ->button(),
                            ]),

                        // Standard Product - Used in Manufacturing
                        Section::make('Produk ini bahan baku dari')
                            ->icon('heroicon-o-arrow-up-circle')
                            ->description('Daftar produk manufaktur yang menggunakan produk ini sebagai bahan baku')
                            ->schema([
                                RepeatableEntry::make('usedInManufacturing')
                                    ->label('Digunakan di Manufaktur')
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Produk')
                                            ->weight('bold')
                                            ->color('primary')
                                            ->url(fn($record) => Pages\ViewProduct::getUrl(['record' => $record->id])),
                                        TextEntry::make('pivot.quantity')
                                            ->label('Kuantitas')
                                            ->formatStateUsing(fn($state, $record) => number_format($state, 2) . ' ' . ($record->unit?->name ?? 'unit')),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Manufacturing Details (BOM)
                Section::make('Produk manufaktur terdiri dari')
                    ->icon('heroicon-o-beaker')
                    ->schema([
                        RepeatableEntry::make('materials')
                            ->label('Bahan Baku')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Bahan'),
                                TextEntry::make('pivot.quantity')
                                    ->label('Kuantitas')
                                    ->formatStateUsing(fn($state, $record) => $state . ' ' . ($record->unit_name ?? 'Pcs')),
                            ])->columns(2)
                    ])
                    ->visible(fn($record) => $record->type === 'manufacturing'),

                // Costs
                Section::make('Biaya produksi terdiri dari')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        RepeatableEntry::make('productionCosts')
                            ->label('Biaya Produksi')
                            ->schema([
                                TextEntry::make('account.name')->label('Biaya'),
                                TextEntry::make('amount')->label('Jumlah')->money('IDR'),
                            ])->columns(2)
                    ])
                    ->visible(fn($record) => $record->type === 'manufacturing'),

                // Charts (moved from Footer Widgets to Infolist to appear above Relations)
                Grid::make(3)
                    ->schema([
                        \Filament\Schemas\Components\View::make('filament.infolists.product-trend-chart')
                            ->columnSpan(2),
                        \Filament\Schemas\Components\View::make('filament.infolists.product-warehouse-split')
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),
            ]);
    }


    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->visibleInProductList()->with(['category', 'unit']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('image')
                    ->label('Foto')
                    ->circular()
                    ->ring(2)
                    ->disk('public')
                    ->defaultImageUrl(url('/images/default-product.png'))
                    ->size(50)
                    ->extraAttributes(['class' => 'product-image-frame'])
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(Product $record) => $record->sku)
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->colors([
                        'gray' => 'standard',
                        'info' => 'service',
                        'warning' => 'manufacturing',
                        'success' => 'bundle',
                        'danger' => 'variant',
                        'primary' => 'fixed_asset',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'standard' => 'Standard',
                        'service' => 'Jasa',
                        'manufacturing' => 'Manufaktur',
                        'bundle' => 'Bundle',
                        'variant' => 'Varian',
                        'fixed_asset' => 'Aset Tetap',
                        default => $state,
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->toggleable(),

                TextColumn::make('unit.name')
                    ->label('Satuan')
                    ->sortable()
                    ->default('-')
                    ->toggleable(),

                TextColumn::make('buy_price')
                    ->label('Harga Beli (DPP)')
                    ->formatStateUsing(function (Product $record) {
                        if ($record->type === 'variant')
                            return '-';
                        $price = (float) $record->buy_price;
                        $taxRate = (int) $record->purchase_tax_id;
                        $includesTax = (bool) $record->purchase_price_includes_tax;
                        $dpp = $includesTax ? ($price * 100 / (100 + $taxRate)) : $price;
                        return 'Rp ' . number_format($dpp, 0, ',', '.');
                    })
                    ->default('Rp 0')
                    ->alignment('right')
                    ->sortable()
                    ->extraAttributes(['class' => 'financial-data'])
                    ->toggleable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Summarizer::make()->label('Total')->money('IDR')->using(fn(\Illuminate\Database\Query\Builder $query) => $query->sum(\Illuminate\Support\Facades\DB::raw('
                        CASE
                            WHEN products.type = "variant" THEN 0
                            WHEN purchase_price_includes_tax THEN (buy_price * 100 / (100 + COALESCE(purchase_tax_id, 0)))
                            ELSE buy_price
                        END
                    ')))),


                TextColumn::make('purchase_tax_id')
                    ->label('Pajak Beli')
                    ->formatStateUsing(function (Product $record) {
                        if ($record->type === 'variant')
                            return '-';
                        $price = (float) $record->buy_price;
                        $taxRate = (int) $record->purchase_tax_id;
                        $includesTax = (bool) $record->purchase_price_includes_tax;

                        if ($taxRate <= 0)
                            return 'Rp 0';

                        if ($includesTax) {
                            $dpp = $price * 100 / (100 + $taxRate);
                            $tax = $price - $dpp;
                        } else {
                            $tax = $price * $taxRate / 100;
                        }

                        return 'Rp ' . number_format($tax, 0, ',', '.');
                    })
                    ->default('Rp 0')
                    ->alignment('right')
                    ->sortable()
                    ->extraAttributes(['class' => 'financial-data'])
                    ->toggleable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Summarizer::make()->label('Total')->money('IDR')->using(fn(\Illuminate\Database\Query\Builder $query) => $query->sum(\Illuminate\Support\Facades\DB::raw('
                        CASE
                            WHEN products.type = "variant" THEN 0
                            WHEN purchase_tax_id <= 0 THEN 0
                            WHEN purchase_price_includes_tax THEN (buy_price - (buy_price * 100 / (100 + purchase_tax_id)))
                            ELSE (buy_price * purchase_tax_id / 100)
                        END
                    ')))),


                TextColumn::make('buy_total_display')
                    ->label('Total Beli')
                    ->state(fn(Product $record) => $record->id)
                    ->formatStateUsing(function (Product $record) {
                        if ($record->type === 'variant')
                            return '-';
                        $price = (float) $record->buy_price;
                        $taxRate = (int) $record->purchase_tax_id;
                        $includesTax = (bool) $record->purchase_price_includes_tax;
                        $total = $includesTax ? $price : ($price * (1 + $taxRate / 100));
                        return new \Illuminate\Support\HtmlString("<span class='font-bold text-amber-600'>Rp " . number_format($total, 0, ',', '.') . "</span>");
                    })
                    ->default('Rp 0')
                    ->alignment('right')
                    ->extraAttributes(['class' => 'financial-data'])
                    ->toggleable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Summarizer::make()->label('Total')->money('IDR')->using(fn(\Illuminate\Database\Query\Builder $query) => $query->sum(\Illuminate\Support\Facades\DB::raw('
                        CASE
                            WHEN products.type = "variant" THEN 0
                            WHEN purchase_price_includes_tax THEN buy_price
                            WHEN purchase_tax_id > 0 THEN (buy_price * (1 + purchase_tax_id / 100))
                            ELSE buy_price
                        END
                    ')))),


                TextColumn::make('sell_price')
                    ->label('Harga Jual (DPP)')
                    ->formatStateUsing(function (Product $record) {
                        if ($record->type === 'variant')
                            return '-';
                        $price = (float) $record->sell_price;
                        $taxRate = (int) $record->sales_tax_id;
                        $includesTax = (bool) $record->sales_price_includes_tax;
                        $dpp = $includesTax ? ($price * 100 / (100 + $taxRate)) : $price;
                        return 'Rp ' . number_format($dpp, 0, ',', '.');
                    })
                    ->default('Rp 0')
                    ->alignment('right')
                    ->sortable()
                    ->extraAttributes(['class' => 'financial-data'])
                    ->toggleable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Summarizer::make()->label('Total')->money('IDR')->using(fn(\Illuminate\Database\Query\Builder $query) => $query->sum(\Illuminate\Support\Facades\DB::raw('
                        CASE
                            WHEN products.type = "variant" THEN 0
                            WHEN sales_price_includes_tax THEN (sell_price * 100 / (100 + COALESCE(sales_tax_id, 0)))
                            ELSE sell_price
                        END
                    ')))),


                TextColumn::make('sales_tax_id')
                    ->label('Pajak Jual')
                    ->formatStateUsing(function (Product $record) {
                        if ($record->type === 'variant')
                            return '-';
                        $price = (float) $record->sell_price;
                        $taxRate = (int) $record->sales_tax_id;
                        $includesTax = (bool) $record->sales_price_includes_tax;

                        if ($taxRate <= 0)
                            return 'Rp 0';

                        if ($includesTax) {
                            $dpp = $price * 100 / (100 + $taxRate);
                            $tax = $price - $dpp;
                        } else {
                            $tax = $price * $taxRate / 100;
                        }

                        return 'Rp ' . number_format($tax, 0, ',', '.');
                    })
                    ->default('Rp 0')
                    ->alignment('right')
                    ->sortable()
                    ->extraAttributes(['class' => 'financial-data'])
                    ->toggleable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Summarizer::make()->label('Total')->money('IDR')->using(fn(\Illuminate\Database\Query\Builder $query) => $query->sum(\Illuminate\Support\Facades\DB::raw('
                        CASE
                            WHEN products.type = "variant" THEN 0
                            WHEN sales_tax_id <= 0 THEN 0
                            WHEN sales_price_includes_tax THEN (sell_price - (sell_price * 100 / (100 + sales_tax_id)))
                            ELSE (sell_price * sales_tax_id / 100)
                        END
                    ')))),


                TextColumn::make('sell_total_display')
                    ->label('Total Jual')
                    ->state(fn(Product $record) => $record->id)
                    ->formatStateUsing(function (Product $record) {
                        if ($record->type === 'variant')
                            return '-';
                        $price = (float) $record->sell_price;
                        $taxRate = (int) $record->sales_tax_id;
                        $includesTax = (bool) $record->sales_price_includes_tax;
                        $total = $includesTax ? $price : ($price * (1 + $taxRate / 100));
                        return new \Illuminate\Support\HtmlString("<span class='font-bold text-emerald-600'>Rp " . number_format($total, 0, ',', '.') . "</span>");
                    })
                    ->default('Rp 0')
                    ->alignment('right')
                    ->extraAttributes(['class' => 'financial-data'])
                    ->toggleable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Summarizer::make()->label('Total')->money('IDR')->using(fn(\Illuminate\Database\Query\Builder $query) => $query->sum(\Illuminate\Support\Facades\DB::raw('
                        CASE
                            WHEN products.type = "variant" THEN 0
                            WHEN sales_price_includes_tax THEN sell_price
                            WHEN sales_tax_id > 0 THEN (sell_price * (1 + sales_tax_id / 100))
                            ELSE sell_price
                        END
                    ')))),


                TextColumn::make('stock')
                    ->label('Qty')
                    ->numeric()
                    ->sortable()
                    ->extraAttributes(['class' => 'financial-data'])
                    ->toggleable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->label('Total')),

                TextColumn::make('cost_of_goods')
                    ->label('HPP')
                    ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->extraAttributes(['class' => 'financial-data'])
                    ->toggleable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Average::make()->label('Rata-rata')->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(bool $state): string => $state ? 'Aktif' : 'Tidak Aktif')
                    ->color(fn(bool $state): string => $state ? 'success' : 'gray')
                    ->toggleable(),
            ])
            ->defaultSort('sku', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'standard' => 'Standard',
                        'service' => 'Service',
                        'manufacturing' => 'Manufacturing',
                        'bundle' => 'Bundle',
                        'fixed_asset' => 'Aset Tetap',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua Produk')
                    ->trueLabel('Hanya Aktif')
                    ->falseLabel('Hanya Tidak Aktif')
                    ->default(true),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock (≤ 10)')
                    ->query(fn(Builder $query): Builder => $query->where('stock', '<=', 10))
                    ->toggle(),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn(Builder $query): Builder => $query->where('stock', '<=', 0))
                    ->toggle(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Lihat'),
                    EditAction::make()
                        ->label('Ubah'),
                    \Filament\Actions\Action::make('sync_to_product')
                        ->label(fn($record) => $record->show_in_products ? 'Nonaktifkan di Produk' : 'Aktifkan di Produk')
                        ->icon(fn($record) => $record->show_in_products ? 'heroicon-m-eye-slash' : 'heroicon-m-eye')
                        ->color(fn($record) => $record->show_in_products ? 'warning' : 'success')
                        ->visible(fn($record) => (bool) $record->is_fixed_asset && $record->status === 'registered')
                        ->requiresConfirmation()
                        ->action(fn($record) => $record->update(['show_in_products' => !$record->show_in_products])),
                    Action::make('print')
                        ->label('Cetak')
                        ->icon('heroicon-o-printer')
                        ->url(fn(Product $record) => Pages\ViewProduct::getUrl([$record->id]))
                        ->openUrlInNewTab(),
                    DeleteAction::make()
                        ->label('Hapus'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('print')
                        ->label('Cetak Terpilih')
                        ->icon('heroicon-o-printer')
                        ->action(fn() => null)
                        ->extraAttributes(['onclick' => 'window.print(); return false;']),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->poll('10s')
            ->emptyStateHeading('No Products Yet')
            ->emptyStateDescription('Start by creating a product or importing your inventory.')
            ->emptyStateIcon('heroicon-o-cube')
            ->recordUrl(fn($record) => Pages\ViewProduct::getUrl([$record->id]));
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StocksRelationManager::class,
            RelationManagers\StockMovementsRelationManager::class,
            RelationManagers\SalesHistoryRelationManager::class,
            RelationManagers\PurchaseHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->active();
    }
}
