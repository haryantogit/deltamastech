<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Log;

class ManufacturingRecipeImporter extends Importer
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
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('type')
                ->label('*Nama Kategori Produk Manufaktur')
                ->castStateUsing(fn() => 'manufacturing'),

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

            // Recipe / Material Columns
            ImportColumn::make('material_sku')
                ->label('*Nama (SKU) Bahan Produk')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('quantity')
                ->label('*Kuantitas Bahan Produk')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric'])
                ->fillRecordUsing(fn() => null),
        ];
    }

    public function resolveRecord(): Product
    {
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

        // Auto-Create as Manufacturing type
        return new Product([
            'sku' => $sku,
            'type' => 'manufacturing',
        ]);
    }

    protected function afterSave(): void
    {
        $materialSku = $this->data['material_sku'] ?? null;
        $quantity = $this->data['quantity'] ?? 0;

        if (blank($materialSku) || $quantity <= 0) {
            return;
        }

        // Find the material product
        $material = Product::where('sku', $materialSku)
            ->orWhere('name', $materialSku)
            ->first();

        if ($material) {
            // Attach or Update pivot
            if ($this->record->materials()->where('material_id', $material->id)->exists()) {
                $this->record->materials()->updateExistingPivot($material->id, ['quantity' => $quantity]);
            } else {
                $this->record->materials()->attach($material->id, ['quantity' => $quantity]);
            }
        } else {
            Log::warning("Material not found: " . $materialSku . " for manufacturing product: " . $this->record->sku);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Manufacturing recipe import completed. ' . Number::format($import->successful_rows) . ' rows processed.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' rows failed.';
        }

        return $body;
    }
}
