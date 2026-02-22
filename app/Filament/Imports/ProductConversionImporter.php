<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ProductConversionImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        // Conversion likely maps basic info + stock? 
        // For now, duplicate standard columns but label appropriately if we had CSV.
        // I'll keep it similar to standard but simpler.
        return [
            ImportColumn::make('name')
                ->label('Nama Produk')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('sku')
                ->label('Kode / SKU')
                ->rules(['max:255']),

            ImportColumn::make('stock')
                ->label('Stok')
                ->numeric()
                ->rules(['nullable', 'numeric']),
        ];
    }

    public function resolveRecord(): Product
    {
        $sku = $this->data['sku'] ?? null;
        return Product::firstOrNew(['sku' => $sku]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Conversion product import completed. ' . Number::format($import->successful_rows) . ' rows processed.';
    }
}
