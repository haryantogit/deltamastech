<?php

namespace App\Filament\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Stock;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Log;

class StandardProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('*Nama Produk')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('sku')
                ->label('Kode / SKU Produk')
                ->rules(['max:255']),

            ImportColumn::make('category')
                ->label('*Kategori Produk')
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('unit')
                ->label('*Unit Produk (Satuan)')
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('buy_price')
                ->label('Harga Pembelian')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->castStateUsing(function ($state) {
                    if (blank($state))
                        return 0;
                    // Remove 'Rp' and spaces
                    $state = str_replace(['Rp', ' '], '', $state);
                    // If it looks like IDR format (15.000,00), standardize it
                    // But CSV sample shows "12061.5".
                    // Trust the is_numeric check first.
                    if (is_numeric($state))
                        return $state;
                    return (float) filter_var($state, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                }),

            ImportColumn::make('sell_price')
                ->label('Harga Penjualan')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->castStateUsing(function ($state) {
                    if (blank($state))
                        return 0;
                    $state = str_replace(['Rp', ' '], '', $state);
                    if (is_numeric($state))
                        return $state;
                    return (float) filter_var($state, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                }),

            ImportColumn::make('import_quantity')
                ->label('Kuantitas')
                ->numeric()
                ->rules(['nullable', 'numeric']),
        ];
    }

    public function resolveRecord(): Product
    {
        $sku = $this->data['sku'] ?? null;

        $query = Product::query();

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(Product::class))) {
            $query->withTrashed();
        }

        $product = $query->where('sku', $sku)->first();

        if ($product) {
            return null; // Skip if SKU exists
        }

        $product = new Product(['sku' => $sku]);

        // Handle Assignments
        if ($unitName = $this->data['unit'] ?? null) {
            $unit = Unit::firstOrCreate(['name' => $unitName]);
            $product->unit_id = $unit->id;
        }

        if ($categoryName = $this->data['category'] ?? null) {
            $category = Category::firstOrCreate(['name' => $categoryName]);
            $product->category_id = $category->id;
        }

        return $product;
    }

    protected function afterSave(): void
    {
        $product = $this->record;
        $quantity = $this->data['import_quantity'] ?? 0;

        // 1. Find/Create Unassigned Warehouse
        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'UA'],
            ['name' => 'Unassigned']
        );

        // 2. Update Stock
        Stock::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
            ['quantity' => $quantity]
        );

        // 3. Create Stock Movement
        \App\Models\StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => $quantity,
            'type' => 'adjustment',
            'reference_type' => \Filament\Actions\Imports\Models\Import::class,
            'reference_id' => $this->import?->id,
        ]);

        // 4. Calculate & Save HPP (Total Value)
        $buyPrice = $product->buy_price ?? 0;
        $totalHpp = $quantity * $buyPrice;

        // Saving "Total Asset Value" into cost_of_goods as per specific instruction
        $product->cost_of_goods = $totalHpp;
        $product->saveQuietly();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
