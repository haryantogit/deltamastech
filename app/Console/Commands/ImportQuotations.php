<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseQuote;
use App\Models\PurchaseQuoteItem;
use App\Models\SalesQuotation;
use App\Models\SalesQuotationItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ImportQuotations extends Command
{
    protected $signature = 'app:import-quotations';
    protected $description = 'Import Purchase and Sales Quotations from CSV files';

    public function handle()
    {
        // Cleanup old wrong data and existing data to avoid duplicates in this run
        DB::table('purchase_quote_items')->delete();
        DB::table('purchase_quotes')->delete();
        DB::table('sales_quotation_items')->delete();
        DB::table('sales_quotations')->delete();
        DB::table('sales_invoice_items')->delete();
        DB::table('sales_invoices')->delete();

        $this->importPurchaseQuotations();
        $this->importSalesQuotations();
        $this->importSalesInvoices();
    }

    private function importPurchaseQuotations()
    {
        $path = 'd:\Program Receh\kledo\data\penawaran-pembelian_30-Jan-2026_halaman-1.csv';
        if (!file_exists($path)) {
            $this->error("File not found: $path");
            return;
        }

        $this->info("Importing Purchase Quotations from $path...");

        $file = fopen($path, 'r');
        $header = fgetcsv($file); // Read header

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($file)) !== false) {
                if (count($row) !== count($header))
                    continue;
                $data = array_combine($header, $row);

                $quoteNumber = $data['*Nomor Penawaran'] ?? null;
                $sku = $data['*Kode Produk (SKU)'] ?? null;
                if (!$quoteNumber || !$sku)
                    continue;

                $contactName = $data['*Nama Kontak'] ?? 'Unknown Vendor';
                $supplier = Contact::firstOrCreate(
                    ['name' => $contactName],
                    ['type' => 'vendor']
                );

                try {
                    $dateRaw = $data['*Tanggal Transaksi (dd/mm/yyyy)'] ?? '';
                    $date = Carbon::createFromFormat('d/m/Y', $dateRaw)->format('Y-m-d');
                } catch (\Exception $e) {
                    $date = now()->format('Y-m-d');
                }

                try {
                    $expiryRaw = $data['*Tanggal Jatuh Tempo (dd/mm/yyyy)'] ?? '';
                    $due_date = Carbon::createFromFormat('d/m/Y', $expiryRaw)->format('Y-m-d');
                } catch (\Exception $e) {
                    $due_date = Carbon::parse($date)->addDays(30)->format('Y-m-d');
                }

                $quotation = PurchaseQuote::create([
                    'number' => $quoteNumber,
                    'date' => $date,
                    'due_date' => $due_date,
                    'supplier_id' => $supplier->id,
                    'status' => 'draft',
                    'tax_inclusive' => ($data['*Termasuk Pajak (Ya / Tidak)'] ?? 'Tidak') === 'Ya',
                ]);

                $product = Product::where('sku', $sku)->first();
                if (!$product) {
                    $this->warn("Product not found: $sku");
                    continue;
                }

                $qty = (float) ($data['*Jumlah Produk'] ?? 0);
                $price = (float) ($data['*Harga Produk'] ?? 0);
                $subtotal = $qty * $price;

                $taxName = $data['Pajak Produk'] ?? 'Bebas Pajak';
                $taxRate = 0;
                $taxId = null;

                // Try to find tax by name
                $tax = \App\Models\Tax::where('name', $taxName)->first();
                if ($tax) {
                    $taxId = $tax->id;
                    $taxRate = $tax->rate / 100;
                } elseif (str_contains(strtolower($taxName), 'ppn')) {
                    // Fallback for CSV legacy data if not in DB
                    $taxRate = 0.11;
                }

                $taxAmount = $subtotal * $taxRate;
                $totalPrice = $subtotal + $taxAmount;

                $unitName = $data['Satuan Produk'] ?? null;
                $unitId = $product->unit_id;
                if ($unitName) {
                    $unit = Unit::firstOrCreate(['name' => $unitName], ['symbol' => $unitName]);
                    $unitId = $unit->id;
                }

                PurchaseQuoteItem::create([
                    'purchase_quote_id' => $quotation->id,
                    'product_id' => $product->id,
                    'tax_id' => $taxId,
                    'description' => $data['Deskripsi Produk'] ?? $product->description,
                    'quantity' => $qty,
                    'unit_id' => $unitId,
                    'unit_price' => $price,
                    'total_price' => $totalPrice,
                    'tax_name' => $taxName,
                    'tax_amount' => $taxAmount,
                ]);
            }

            // Recalculate Totals
            foreach (PurchaseQuote::get() as $q) {
                $q->sub_total = $q->items()->sum(DB::raw('quantity * unit_price'));
                $q->total_amount = $q->items()->sum('total_price');
                $q->save();
            }

            DB::commit();
            $this->info("Purchase Quotations Imported Successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
        }
    }

    private function importSalesQuotations()
    {
        $path = 'd:\Program Receh\kledo\data\penawaran_30-Jan-2026_halaman-1.csv';
        if (!file_exists($path)) {
            $this->error("File not found: $path");
            return;
        }

        $this->info("Importing Sales Quotations from $path...");
        $file = fopen($path, 'r');
        $header = fgetcsv($file);

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($file)) !== false) {
                if (count($row) !== count($header))
                    continue;
                $data = array_combine($header, $row);

                $quoteNumber = $data['*Nomor Penawaran'] ?? null;
                $sku = $data['*Kode Produk (SKU)'] ?? null;
                if (!$quoteNumber || !$sku)
                    continue;

                $contactName = $data['*Nama Kontak'] ?? 'Unknown Customer';
                $contact = Contact::firstOrCreate(
                    ['name' => $contactName],
                    ['type' => 'customer']
                );

                try {
                    $dateRaw = $data['*Tanggal Transaksi (dd/mm/yyyy)'] ?? '';
                    $date = Carbon::createFromFormat('d/m/Y', $dateRaw)->format('Y-m-d');
                } catch (\Exception $e) {
                    $date = now()->format('Y-m-d');
                }

                $statusRaw = $data['Status'] ?? 'Draft';
                $statusMap = ['Disetujui' => 'accepted', 'Draft' => 'draft', 'Terkirim' => 'sent', 'Ditolak' => 'rejected'];
                $status = $statusMap[$statusRaw] ?? 'draft';

                $quotation = SalesQuotation::create([
                    'number' => $quoteNumber,
                    'date' => $date,
                    'contact_id' => $contact->id,
                    'status' => $status,
                    'total_amount' => 0
                ]);

                $product = Product::where('sku', $sku)->first();
                if (!$product) {
                    $this->warn("Product not found: $sku");
                    continue;
                }

                $qty = (float) ($data['*Jumlah Produk'] ?? 0);
                $price = (float) ($data['*Harga Produk'] ?? 0);
                $subtotal = $qty * $price;

                $itemTaxAmt = 0;
                if (($data['Pajak Produk'] ?? '') == 'PPN') {
                    $itemTaxAmt = $subtotal * 0.11;
                }
                $totalPrice = $subtotal + $itemTaxAmt;

                SalesQuotationItem::create([
                    'sales_quotation_id' => $quotation->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total_price' => $totalPrice,
                ]);
            }

            foreach (SalesQuotation::get() as $pq) {
                $pq->total_amount = $pq->items()->sum('total_price');
                $pq->save();
            }

            DB::commit();
            $this->info("Sales Quotations Imported Successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
        }
    }

    private function parseDate($dateRaw)
    {
        if (!$dateRaw)
            return now()->format('Y-m-d');

        // Handle Excel numeric dates (days since 1899-12-30)
        if (is_numeric($dateRaw) && $dateRaw > 40000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $dateRaw)->format('Y-m-d');
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $dateRaw)->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($dateRaw)->format('Y-m-d');
            } catch (\Exception $e2) {
                return now()->format('Y-m-d');
            }
        }
    }

    private function importSalesInvoices()
    {
        $path = 'd:\Program Receh\kledo\data\tagihan_29-Jan-2026_halaman-1.csv';
        if (!file_exists($path)) {
            $this->error("File not found: $path");
            return;
        }

        $this->info("Importing Sales Invoices from $path...");
        $file = fopen($path, 'r');
        $header = fgetcsv($file);

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($file)) !== false) {
                if (count($row) !== count($header))
                    continue;
                $data = array_combine($header, $row);

                $invoiceNumber = $data['*Nomor Tagihan'] ?? null;
                $sku = $data['*Kode Produk (SKU)'] ?? null;
                if (!$invoiceNumber || !$sku)
                    continue;

                $contactName = $data['*Nama Kontak'] ?? 'Unknown Customer';
                $contact = Contact::firstOrCreate(
                    ['name' => $contactName],
                    ['type' => 'customer']
                );

                $date = $this->parseDate($data['*Tanggal Transaksi (dd/mm/yyyy)'] ?? null);
                $dueDate = $this->parseDate($data['*Tanggal Jatuh Tempo (dd/mm/yyyy)'] ?? null);
                $shippingDate = $this->parseDate($data['Tanggal Pengiriman (dd/mm/yyyy)'] ?? null);

                $statusRaw = $data['Status'] ?? 'Belum Dibayar';
                $statusMap = [
                    'Lunas' => 'paid',
                    'Belum Dibayar' => 'unpaid',
                    'Dibayar Sebagian' => 'partial',
                    'Terlambat' => 'overdue',
                    'Draft' => 'draft'
                ];
                $status = $statusMap[$statusRaw] ?? 'unpaid';

                // Account mapping
                $accountCode = $data['Kode Akun Pembayaran'] ?? '1-10002';
                $account = \App\Models\Account::where('code', $accountCode)->first();

                // Warehouse mapping
                $warehouseName = $data['*Nama / Kode Gudang'] ?? 'Unassigned';
                $warehouse = Warehouse::where('name', $warehouseName)->first();

                $invoice = SalesInvoice::firstOrCreate(
                    ['invoice_number' => $invoiceNumber],
                    [
                        'contact_id' => $contact->id,
                        'transaction_date' => $date,
                        'due_date' => $dueDate,
                        'shipping_date' => $shippingDate,
                        'status' => $status,
                        'account_id' => $account?->id,
                        'warehouse_id' => $warehouse?->id,
                        'notes' => $data['Catatan'] ?? null,
                        'reference' => $data['Ekspedisi / Pengiriman Kurir'] ?? null,
                        'total_amount' => 0,
                        'balance_due' => 0,
                    ]
                );

                $product = Product::where('sku', $sku)->first();
                if (!$product) {
                    $this->warn("Product not found: $sku");
                    continue;
                }

                $qty = (float) str_replace(',', '', $data['*Jumlah Produk'] ?? 0);
                $price = (float) str_replace(',', '', $data['*Harga Produk'] ?? 0);
                $subtotal = $qty * $price;

                $taxName = $data['Pajak Produk'] ?? 'Bebas Pajak';
                $taxAmount = 0;
                if (str_contains(strtolower($taxName), 'ppn')) {
                    $taxAmount = $subtotal * 0.11;
                }
                $subtotalWithTax = $subtotal + $taxAmount;

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'unit_id' => $product->unit_id,
                    'description' => $data['Deskripsi Produk'] ?? $product->description,
                    'qty' => $qty,
                    'price' => $price,
                    'tax_name' => $taxName,
                    'tax_amount' => $taxAmount,
                    'subtotal' => $subtotalWithTax,
                ]);
            }

            // Recalculate Totals
            foreach (SalesInvoice::all() as $inv) {
                $inv->total_amount = $inv->items()->sum('subtotal');
                $inv->balance_due = $inv->total_amount;
                if ($inv->status === 'paid') {
                    $inv->balance_due = 0;
                }
                $inv->save();
            }

            DB::commit();
            $this->info("Sales Invoices Imported Successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        }
    }
}
