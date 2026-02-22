<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Log;

class ProductBundleImporter extends Importer
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
                ->rules(['max:255']),

            ImportColumn::make('type')
                ->label('*Kategori Produk Paket')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->castStateUsing(fn() => 'bundle'), // Force type

            ImportColumn::make('sell_price')
                ->label('Harga Produk Paket')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->castStateUsing(fn($state) => (blank($state) || !is_numeric($state)) ? 0 : $state),

            ImportColumn::make('unit')
                ->label('*Satuan Produk Paket')
                ->rules(['max:255']),

            // Bundle Items
            ImportColumn::make('item_name')
                ->label('*Nama / SKU Produk')
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('item_quantity')
                ->label('*Jumlah Produk')
                ->rules(['nullable', 'numeric']),
        ];
    }

    public function resolveRecord(): Product
    {
        Log::info('Importing Bundle Row:', $this->data);
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
        $itemName = $this->data['item_name'] ?? null;
        $itemQty = $this->data['item_quantity'] ?? 0;

        if (blank($itemName) || $itemQty <= 0) {
            return;
        }

        $item = Product::where('name', $itemName)
            ->orWhere('sku', $itemName)
            ->first();

        if ($item) {
            // Use bundleItems() relation
            if ($this->record->bundleItems()->where('item_id', $item->id)->exists()) {
                $this->record->bundleItems()->updateExistingPivot($item->id, ['quantity' => $itemQty]);
            } else {
                $this->record->bundleItems()->attach($item->id, ['quantity' => $itemQty]);
            }
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Bundle product import completed. ' . Number::format($import->successful_rows) . ' rows processed.';
    }
}
