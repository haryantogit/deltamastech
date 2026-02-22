<?php

namespace App\Filament\Imports;

use App\Models\PurchaseQuotation;
use App\Models\PurchaseQuotationItem;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Unit;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Carbon;

class PurchaseQuotationImporter extends Importer
{
    protected static ?string $model = PurchaseQuotationItem::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('quote_number')
                ->label('*Nomor Penawaran')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('supplier_name')
                ->label('*Nama Kontak')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('transaction_date')
                ->label('*Tanggal Transaksi (dd/mm/yyyy)')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('expiry_date')
                ->label('*Tanggal Jatuh Tempo (dd/mm/yyyy)')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('warehouse_name')
                ->label('*Nama / Kode Gudang')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('product_sku')
                ->label('*Kode Produk (SKU)')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('product_description')
                ->label('Deskripsi Produk')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('qty')
                ->label('*Jumlah Produk')
                ->requiredMapping()
                ->numeric(),

            ImportColumn::make('unit_name')
                ->label('Satuan Produk')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('price')
                ->label('*Harga Produk')
                ->requiredMapping()
                ->numeric(),

            ImportColumn::make('tax_name')
                ->label('Pajak Produk')
                ->fillRecordUsing(fn() => null),
        ];
    }

    public function resolveRecord(): ?PurchaseQuotationItem
    {
        $row = $this->data;

        // 1. Validasi Data Utama
        $quoteNumber = $row['quote_number'] ?? null;
        $sku = $row['product_sku'] ?? null;
        $supplierName = $row['supplier_name'] ?? null;

        if (!$quoteNumber || !$sku) {
            return null; // Skip jika data tidak lengkap
        }

        // 2. Cari / Buat Vendor (Auto-Create)
        $supplier = Contact::firstOrCreate(
            ['name' => $supplierName],
            ['type' => 'vendor'] // Tipe Vendor
        );

        // 3. Cari Produk (Wajib Ada)
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            // Optional: Create product if allowed or throw error
            throw new \Exception("Produk SKU '{$sku}' tidak ditemukan. Mohon cek Master Produk.");
        }

        // 4. Cari / Buat Unit
        $unitId = $product->unit_id;
        if (!empty($row['unit_name'])) {
            $unit = Unit::firstOrCreate(['name' => $row['unit_name']], ['symbol' => $row['unit_name']]);
            $unitId = $unit->id;
        }

        // 5. Parsing Tanggal (Format: dd/mm/yyyy)
        try {
            $dateRaw = $row['transaction_date'] ?? '';
            $transactionDate = Carbon::createFromFormat('d/m/Y', $dateRaw)->format('Y-m-d');
        } catch (\Exception $e) {
            $transactionDate = now()->format('Y-m-d');
        }

        try {
            $expiryRaw = $row['expiry_date'] ?? '';
            $expiryDate = Carbon::createFromFormat('d/m/Y', $expiryRaw)->format('Y-m-d');
        } catch (\Exception $e) {
            $expiryDate = Carbon::parse($transactionDate)->addDays(30)->format('Y-m-d');
        }

        // 6. Cari / Buat Parent (Purchase Quotation)
        $quotation = PurchaseQuotation::firstOrCreate(
            ['number' => $quoteNumber],
            [
                'date' => $transactionDate,
                'expiry_date' => $expiryDate,
                'supplier_id' => $supplier->id,
                'status' => 'draft', // Default draft
                'warehouse_id' => \App\Models\Warehouse::where('name', $row['warehouse_name'] ?? '')->first()?->id,
                'total_amount' => 0,
            ]
        );

        // 7. Calculate Amounts
        $qty = floatval($row['qty'] ?? 0);
        $price = floatval($row['price'] ?? 0);
        $subtotal = $qty * $price;

        $taxName = $row['tax_name'] ?? 'Bebas Pajak';
        $taxRate = 0;
        if (str_contains(strtolower($taxName), 'ppn 11'))
            $taxRate = 0.11;
        elseif (str_contains(strtolower($taxName), 'ppn 12'))
            $taxRate = 0.12;
        elseif (strtolower($taxName) === 'ppn')
            $taxRate = 0.11; // Assumption

        $taxAmount = $subtotal * $taxRate;
        $totalPrice = $subtotal + $taxAmount;

        // 8. Return Item
        return new PurchaseQuotationItem([
            'purchase_quotation_id' => $quotation->id,
            'product_id' => $product->id,
            'description' => $row['product_description'] ?? $product->description,
            'quantity' => $qty,
            'unit_id' => $unitId,
            'unit_price' => $price,
            'tax_name' => $taxName,
            'tax_amount' => $taxAmount,
            'total_price' => $totalPrice,
            'discount_percent' => 0,
            'discount_amount' => 0,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Purchase quotation import completed. ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed.';
        }

        return $body;
    }
}
