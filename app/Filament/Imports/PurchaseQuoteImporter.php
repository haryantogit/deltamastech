<?php

namespace App\Filament\Imports;

use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseQuote;
use App\Models\PurchaseQuoteItem;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Carbon\Carbon;

class PurchaseQuoteImporter extends Importer
{
    protected static ?string $model = PurchaseQuote::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('number')
                ->label('*Nomor Penawaran')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('supplier_name')
                ->label('*Nama Kontak')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('dateRaw')
                ->label('Tanggal (dd/mm/yyyy)'),
            ImportColumn::make('sku')
                ->label('*Kode Produk (SKU)')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('qty')
                ->label('*Jumlah Produk')
                ->numeric()
                ->rules(['required', 'numeric', 'min:0']),
            ImportColumn::make('unit_price')
                ->label('*Harga Produk')
                ->numeric()
                ->rules(['required', 'numeric', 'min:0']),
            ImportColumn::make('total_amount')
                ->label('Total') // Header is 'Total' in example
                ->numeric(),
        ];
    }

    public function resolveRecord(): ?PurchaseQuote
    {
        $number = $this->data['number'];
        $supplierName = $this->data['supplier_name'];
        $sku = $this->data['sku'];
        $qty = $this->data['qty'];
        $price = $this->data['unit_price'];
        $totalAmount = $this->data['total_amount'] ?? 0;
        $dateRaw = $this->data['dateRaw'] ?? null;

        // Parse Date
        $date = now();
        if ($dateRaw) {
            try {
                $date = Carbon::createFromFormat('d/m/Y', $dateRaw);
            } catch (\Exception $e) {
                // Fallback to now if parse fails
            }
        }

        // Find or Create Supplier
        $supplier = Contact::firstOrCreate(
            ['name' => $supplierName],
            ['type' => 'vendor'] // Default type if creating new
        );

        // Find Product
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            // Log warning or skip? For now we can skip item creation if product not found, 
            // but we still need the Quote header? 
            // Better to return null and let it fail?
            // Or maybe create a placeholder product? 
            // Sticking to "Product must exist" logic usually.
            // But let's assume valid SKU for now or handle gracefully.
            return null;
        }

        // Find or Create Quote
        $quote = PurchaseQuote::firstOrCreate(
            ['number' => $number],
            [
                'supplier_id' => $supplier->id,
                'date' => $date,
                'status' => 'open',
                'total_amount' => $totalAmount, // Initial total
            ]
        );

        // Update total if provided and different?
        if ($quote->wasRecentlyCreated === false && $totalAmount > 0) {
            // Maybe update total? Or keep existing?
            // $quote->update(['total_amount' => $totalAmount]);
        }

        // Create Item
        PurchaseQuoteItem::create([
            'purchase_quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'unit_price' => $price,
            'total_price' => $qty * $price,
        ]);

        return $quote;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your purchase quote import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
