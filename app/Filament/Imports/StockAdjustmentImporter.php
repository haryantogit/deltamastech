<?php

namespace App\Filament\Imports;

use App\Models\StockAdjustment;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class StockAdjustmentImporter extends Importer
{
    protected static ?string $model = \App\Models\StockAdjustmentItem::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('product_name')
                ->label('Nama Produk')
                ->example('Produk A')
                ->fillRecordUsing(fn() => null),
            ImportColumn::make('sku')
                ->label('Kode Produk')
                ->requiredMapping()
                ->rules(['required'])
                ->fillRecordUsing(fn() => null),
            ImportColumn::make('quantity')
                ->label('Kuantitas')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric']),
            ImportColumn::make('total_value')
                ->label('Nilai Produk')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),
        ];
    }

    public function resolveRecord(): ?\App\Models\StockAdjustmentItem
    {
        $sku = trim($this->data['sku']);
        $quantity = (float) $this->data['quantity'];
        $totalValRaw = $this->data['total_value'] ?? 0;

        // Clean currency formatting if any (remove "Rp", ".", etc, keep digits and comma/dot)
        // Assuming format like "1.000,00" or "1000" or "$1000". 
        // Simple numeric clean: remove non-numeric chars except dot and comma.
        // Actually best to interpret specific format. If standard numeric, simple cast.
        // If "Rp 56.700", we need to remove "Rp" and " ".
        // Let's replace anything that is not digit, dot, or minus.
        // Wait, Indonesian format uses dot for thousands and comma for decimals.
        // e.g. 56.700 = 56700. 
        // If import is standard float (56700.00), it's easy.
        // I will assume it renders as number or user maps a number.
        // I will try to parse simplified: remove non-digits.
        // Actually, let's keep it robust: filter all except numbers and dots (if dot decimal).

        $totalValue = (float) filter_var($totalValRaw, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        \Illuminate\Support\Facades\Log::info("Starting Import for SKU: {$sku}, Qty: {$quantity}, Total Val: {$totalValue}");

        // Strict SKU search
        $product = \App\Models\Product::where('sku', $sku)->first();

        if (!$product) {
            \Illuminate\Support\Facades\Log::error("Import Failed: Product not found for SKU: {$sku}");
            return null;
        }

        // Update Product Cost (Buy Price)
        if ($quantity > 0 && $totalValue > 0) {
            $unitCost = $totalValue / $quantity;
            $product->update(['buy_price' => $unitCost]);
            \Illuminate\Support\Facades\Log::info("Product Cost Updated: {$product->name} -> New Buy Price: {$unitCost}");
        }

        // Get Warehouse from options
        $warehouseId = $this->options['warehouse_id'] ?? null;
        $transactionDate = $this->options['transaction_date'];
        $notes = $this->options['notes'] ?? 'Import #' . $this->import->id;

        if (!$warehouseId) {
            // Fallback: Use the first warehouse found
            $defaultWarehouse = \App\Models\Warehouse::first();
            if ($defaultWarehouse) {
                $warehouseId = $defaultWarehouse->id;
                \Illuminate\Support\Facades\Log::warning("Import Warning: Warehouse not selected. Using default: {$defaultWarehouse->name} (ID: {$warehouseId})");
            } else {
                \Illuminate\Support\Facades\Log::error("Import Crushed: No warehouse found.");
                throw new \Exception('No warehouse found in database. Please create a warehouse first.');
            }
        }

        // Find or Create Header for this Import Session
        $importId = $this->import->id;
        $dateStr = date('Ymd', strtotime($transactionDate));

        $number = "SA-{$dateStr}-{$importId}";

        $adjustment = StockAdjustment::firstOrCreate(
            ['number' => $number],
            [
                'date' => $transactionDate,
                'warehouse_id' => $warehouseId,
                'notes' => $notes,
            ]
        );


        // IMMEDIATE STOCK UPDATE
        try {
            \App\Models\Stock::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                ],
                [
                    'quantity' => \Illuminate\Support\Facades\DB::raw("quantity + " . (float) $quantity)
                ]
            );
            \Illuminate\Support\Facades\Log::info("Stock Updated Successfully: Product {$product->name} (ID: {$product->id}) -> Added {$quantity}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Stock Update Failed DB Error: " . $e->getMessage());
            throw $e;
        }

        return new \App\Models\StockAdjustmentItem([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    public static function getOptionsForm(): array
    {
        return [
            \Filament\Forms\Components\Select::make('warehouse_id')
                ->label('Gudang')
                ->options(\App\Models\Warehouse::pluck('name', 'id'))
                ->required()
                ->searchable(),
            \Filament\Forms\Components\DatePicker::make('transaction_date')
                ->label('Tanggal Transaksi')
                ->default(\Carbon\Carbon::parse('2026-01-23'))
                ->required(),
            \Filament\Forms\Components\TextInput::make('notes')
                ->label('Catatan')
                ->placeholder('Contoh: Saldo Awal 2026'),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Penyesuaian stok berhasil diimport. ' . Number::format($import->successful_rows) . ' item diproses.';
    }
}
