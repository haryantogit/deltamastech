<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Log;

class ProductImporter extends Importer
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

            ImportColumn::make('type')
                ->label('*Kategori Produk')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->castStateUsing(function ($state): ?string {
                    $state = (string) $state;
                    if (str_contains($state, 'Jasa')) {
                        return 'service';
                    }
                    return 'standard';
                }),

            ImportColumn::make('buy_price')
                ->label('Harga Pembelian')
                ->requiredMapping()
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->castStateUsing(fn($state) => (blank($state) || !is_numeric($state)) ? 0 : $state),

            ImportColumn::make('sell_price')
                ->label('Harga Penjualan')
                ->requiredMapping()
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->castStateUsing(fn($state) => (blank($state) || !is_numeric($state)) ? 0 : $state),

            ImportColumn::make('cost_of_goods')
                ->label('Harga Pokok Penjualan (HPP)')
                ->requiredMapping()
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->castStateUsing(fn($state) => (blank($state) || !is_numeric($state)) ? 0 : $state),

            ImportColumn::make('stock')
                ->label('Kuantitas')
                ->requiredMapping()
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->castStateUsing(fn($state) => (blank($state) || !is_numeric($state)) ? 0 : $state),

            ImportColumn::make('min_stock')
                ->label('Stok Minimal')
                ->rules(['nullable', 'numeric'])
                ->castStateUsing(fn($state) => (blank($state) || !is_numeric($state)) ? 0 : $state),

            ImportColumn::make('unit')
                ->label('*Unit Produk (Satuan)')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): Product
    {
        Log::info('Importing Standard Row:', $this->data);
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

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
