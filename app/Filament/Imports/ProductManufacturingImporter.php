<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Log;

class ProductManufacturingImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('*Nama Produk Manufaktur')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('sku')
                ->label('*Kode (SKU) Produk Manufaktur')
                ->rules(['max:255']),

            ImportColumn::make('type')
                ->label('*Kategori Produk Manufaktur')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->castStateUsing(fn() => 'manufacturing'), // Force type

            // Manufacturing typically uses cost_of_goods logic or auto-calculated.
            // CSV has "Harga Beli" (Buy Price) and "Harga Jual" (Sell Price).
            ImportColumn::make('buy_price')
                ->label('Harga Beli Produk Manufaktur')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->castStateUsing(fn($state) => (blank($state) || !is_numeric($state)) ? 0 : $state),

            ImportColumn::make('sell_price')
                ->label('Harga Jual Produk Manufaktur')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->castStateUsing(fn($state) => (blank($state) || !is_numeric($state)) ? 0 : $state),

            ImportColumn::make('unit')
                ->label('*Satuan Produk Manufaktur')
                ->rules(['max:255']),

            // Ingredients
            ImportColumn::make('material_name')
                ->label('*Nama (SKU) Bahan Produk')
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('material_quantity')
                ->label('*Kuantitas Bahan Produk')
                ->rules(['nullable', 'numeric']),
        ];
    }

    public function resolveRecord(): Product
    {
        Log::info('Importing Manufacturing Row:', $this->data);
        $sku = $this->data['sku'] ?? null;

        $query = Product::query();

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(Product::class))) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query->withTrashed();
        }

        $product = $query->where('sku', $sku)->first();

        if ($product) {
            if (method_exists($product, 'trashed') && $product->trashed()) {
                $product->restore();
            }
            return $product;
        }

        return new Product(['sku' => $sku]);
    }

    protected function afterSave(): void
    {
        $materialName = $this->data['material_name'] ?? null;
        $materialQty = $this->data['material_quantity'] ?? 0;

        if (blank($materialName) || $materialQty <= 0) {
            return;
        }

        $material = Product::where('name', $materialName)
            ->orWhere('sku', $materialName)
            ->first();

        if ($material) {
            if ($this->record->materials()->where('material_id', $material->id)->exists()) {
                $this->record->materials()->updateExistingPivot($material->id, ['quantity' => $materialQty]);
            } else {
                $this->record->materials()->attach($material->id, ['quantity' => $materialQty]);
            }
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Manufacturing product import completed. ' . Number::format($import->successful_rows) . ' rows processed.';
    }
}
