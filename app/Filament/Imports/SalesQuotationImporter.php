<?php

namespace App\Filament\Imports;

use App\Models\SalesQuotation;
use App\Models\SalesQuotationItem;
use App\Models\Contact;
use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Carbon;

class SalesQuotationImporter extends Importer
{
    protected static ?string $model = SalesQuotationItem::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('quote_number')
                ->label('*Nomor Penawaran')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('customer_name')
                ->label('*Nama Kontak')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('transaction_date')
                ->label('*Tanggal Transaksi (dd/mm/yyyy)')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('status')
                ->label('Status')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('product_sku')
                ->label('*Kode Produk (SKU)')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('qty')
                ->label('*Jumlah Produk')
                ->requiredMapping()
                ->numeric(),

            ImportColumn::make('price')
                ->label('*Harga Produk')
                ->requiredMapping()
                ->numeric(),
        ];
    }

    public function resolveRecord(): ?SalesQuotationItem
    {
        $row = $this->data;

        // 1. Validasi Data Utama
        $quoteNumber = $row['quote_number'] ?? null;
        $sku = $row['product_sku'] ?? null;
        $customerName = $row['customer_name'] ?? null;

        if (!$quoteNumber || !$sku) {
            return null; // Skip jika data tidak lengkap
        }

        // 2. Cari / Buat Customer (Auto-Create)
        $customer = Contact::firstOrCreate(
            ['name' => $customerName],
            ['type' => 'customer']
        );

        // 3. Cari Produk (Wajib Ada)
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            throw new \Exception("Produk SKU '{$sku}' tidak ditemukan. Mohon cek Master Produk.");
        }

        // 4. Parsing Tanggal (Format: dd/mm/yyyy)
        try {
            $dateRaw = $row['transaction_date'] ?? '';
            $transactionDate = Carbon::createFromFormat('d/m/Y', $dateRaw)->format('Y-m-d');
        } catch (\Exception $e) {
            $transactionDate = now()->format('Y-m-d'); // Fallback ke hari ini jika gagal
        }

        // 5. Mapping Status Indonesia -> System Enum
        $statusMap = [
            'Disetujui' => 'accepted',
            'Terkirim' => 'sent',
            'Ditolak' => 'rejected',
            'Draft' => 'draft',
        ];
        $statusRaw = $row['status'] ?? 'Draft';
        $systemStatus = $statusMap[$statusRaw] ?? 'draft';

        // 6. Cari / Buat Parent (Sales Quotation)
        $quotation = SalesQuotation::firstOrCreate(
            ['number' => $quoteNumber],
            [
                'date' => $transactionDate,
                'contact_id' => $customer->id,
                'status' => $systemStatus,
                'total_amount' => 0, // Nanti dihitung ulang
            ]
        );

        // 7. Return Item (Barang)
        return new SalesQuotationItem([
            'sales_quotation_id' => $quotation->id,
            'product_id' => $product->id,
            'quantity' => floatval($row['qty'] ?? 0),
            'unit_price' => floatval($row['price'] ?? 0),
            'total_price' => floatval($row['qty'] ?? 0) * floatval($row['price'] ?? 0),
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your sales quotation import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
