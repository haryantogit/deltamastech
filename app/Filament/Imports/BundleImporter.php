<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Log;

class BundleImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('*Nama Produk Paket')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('sku')
                ->label('*Kode / SKU Produk Paket')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('type')
                ->label('*Kategori Produk Paket')
                ->castStateUsing(fn() => 'bundle'),

            ImportColumn::make('sell_price')
                ->label('Harga Produk Paket')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->castStateUsing(fn($state) => (blank($state) || !is_numeric($state)) ? 0 : $state),

            ImportColumn::make('unit')
                ->label('*Satuan Produk Paket')
                ->rules(['max:255']),

            // Bundle Item Columns
            ImportColumn::make('item_sku')
                ->label('*Nama / SKU Produk')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('quantity')
                ->label('*Jumlah Produk')
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

        // Auto-Create as Bundle type
        return new Product([
            'sku' => $sku,
            'type' => 'bundle',
        ]);
    }

    protected function afterSave(): void
    {
        $itemSku = $this->data['item_sku'] ?? null;
        $quantity = $this->data['quantity'] ?? 0;

        if (blank($itemSku) || $quantity <= 0) {
            return;
        }

        // Find the child product
        $childItem = Product::where('sku', $itemSku)
            ->orWhere('name', $itemSku)
            ->first();

        if ($childItem) {
            // Attach or Update pivot
            if ($this->record->bundleItems()->where('item_id', $childItem->id)->exists()) {
                $this->record->bundleItems()->updateExistingPivot($childItem->id, ['quantity' => $quantity]);
            } else {
                $this->record->bundleItems()->attach($childItem->id, ['quantity' => $quantity]);
            }
        } else {
            Log::warning("Bundle item not found: " . $itemSku . " for bundle product: " . $this->record->sku);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Bundle import completed. ' . Number::format($import->successful_rows) . ' rows processed.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' rows failed.';
        }

        return $body;
    }
}
