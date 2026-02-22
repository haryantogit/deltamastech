<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Tax;
use Carbon\Carbon;

class ImportPurchaseOrdersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = 'D:\Program Receh\kledo\data\pesanan-pembelian_29-Jan-2026_halaman-1.csv';

        if (!file_exists($csvFile)) {
            $this->command->error("File not found: $csvFile");
            return;
        }

        $file = fopen($csvFile, 'r');
        $header = fgetcsv($file); // Skip header

        // Map header to index
        $headerMap = [];
        foreach ($header as $index => $column) {
            $headerMap[trim($column)] = $index;
        }

        $orders = [];

        while (($row = fgetcsv($file)) !== false) {
            // Helper to get value by column name
            $getVal = fn($col) => isset($headerMap[$col]) ? trim($row[$headerMap[$col]]) : null;

            $orderNumber = $getVal('*Nomor Pesanan');
            // Log::info("Row Order Number: " . $orderNumber); 
            if (empty($orderNumber)) {
                // Log::warning("Skipping row due to empty order number");
                continue;
            }

            if (!isset($orders[$orderNumber])) {
                $orders[$orderNumber] = [
                    'contact_name' => $getVal('*Nama Kontak'),
                    'company' => $getVal('Perusahaan'),
                    'email' => $getVal('Email'),
                    'phone' => $getVal('Nomor Telepon'),
                    'address' => $getVal('Alamat'),
                    'transaction_date' => $getVal('*Tanggal Transaksi (dd/mm/yyyy)'),
                    'due_date' => $getVal('*Tanggal Jatuh Tempo (dd/mm/yyyy)'),
                    'warehouse_name' => $getVal('*Nama / Kode Gudang'),
                    'notes' => $getVal('Catatan'),
                    'tax_inclusive' => strtolower($getVal('*Termasuk Pajak (Ya / Tidak)')) === 'ya',
                    'shipping_cost' => $this->parseNumber($getVal('Biaya Pengiriman')),
                    'discount_amount' => $this->parseNumber($getVal('Jumlah Diskon Tambahan')),
                    'down_payment' => $this->parseNumber($getVal('Jumlah Uang Muka')),
                    'total_amount' => $this->parseNumber($getVal('Total')),
                    'sub_total' => $this->parseNumber($getVal('Subtotal Produk')),
                    'items' => [],
                ];
            }

            // Parse product row
            $orders[$orderNumber]['items'][] = [
                'product_name' => $getVal('*Nama Produk'),
                'sku' => $getVal('*Kode Produk (SKU)'),
                'description' => $getVal('Deskripsi Produk'),
                'quantity' => $this->parseNumber($getVal('*Jumlah Produk')),
                'unit' => $getVal('Satuan Produk'),
                'unit_price' => $this->parseNumber($getVal('*Harga Produk')),
                'tax_name' => $getVal('Pajak Produk'), // e.g. "PPN"
                'discount_percent' => $this->parseNumber($getVal('Diskon Produk (Persen)')),
                'discount_amount' => $this->parseNumber($getVal('Diskon Produk')),
            ];
        }

        $this->command->info("Found " . count($orders) . " unique orders in CSV.");

        fclose($file);

        foreach ($orders as $orderNumber => $data) {
            $this->command->info("Processing Order: $orderNumber");

            DB::beginTransaction();
            try {
                // 1. Find or Create Contact
                $contactName = $data['contact_name'];

                $contact = Contact::firstOrCreate(
                    ['name' => $contactName],
                    [
                        'company' => $data['company'],
                        'email' => $data['email'],
                        'phone' => $data['phone'],
                        'address' => $data['address'],
                        'type' => 'supplier'
                    ]
                );

                // 2. Find or Create Warehouse
                $warehouseName = $data['warehouse_name'] ?: 'Unassigned';
                $warehouse = Warehouse::firstOrCreate(
                    ['name' => $warehouseName],
                    ['code' => strtoupper(substr($warehouseName, 0, 3))]
                );

                // 3. Create Order
                $date = $this->parseDate($data['transaction_date']);
                $dueDate = $this->parseDate($data['due_date']);

                $order = PurchaseOrder::updateOrCreate(
                    ['number' => $orderNumber],
                    [
                        'date' => $date,
                        'due_date' => $dueDate,
                        'supplier_id' => $contact->id,
                        'warehouse_id' => $warehouse->id,
                        'notes' => $data['notes'],
                        'tax_inclusive' => $data['tax_inclusive'],
                        'shipping_cost' => $data['shipping_cost'],
                        'discount_amount' => $data['discount_amount'],
                        'down_payment' => $data['down_payment'],
                        'total_amount' => $data['total_amount'],
                        'sub_total' => $data['sub_total'],
                        'status' => 'approved',
                    ]
                );

                // 4. Create Items
                $order->items()->delete();

                foreach ($data['items'] as $itemData) {
                    // Find or Create Unit
                    $unitId = null;
                    if (!empty($itemData['unit'])) {
                        $unit = \App\Models\Unit::firstOrCreate(['name' => $itemData['unit']]);
                        $unitId = $unit->id;
                    }

                    // Find or Create Product
                    $sku = $itemData['sku'];
                    $productAttributes = [
                        'unit_name' => $itemData['unit'],
                        'buy_price' => $itemData['unit_price'],
                        'description' => $itemData['description'],
                        'type' => 'standard',
                        'track_inventory' => true
                    ];

                    if (!empty($sku)) {
                        // If SKU exists, use it to find or create
                        $product = Product::firstOrCreate(
                            ['sku' => $sku],
                            array_merge(['name' => $itemData['product_name']], $productAttributes)
                        );
                    } else {
                        // Fallback to name if no SKU
                        $product = Product::firstOrCreate(
                            ['name' => $itemData['product_name']],
                            array_merge(['sku' => 'SKU-' . uniqid()], $productAttributes)
                        );
                    }

                    // Handle Tax
                    $taxId = null;
                    $taxRate = 0;
                    $taxName = null;
                    if (!empty($itemData['tax_name']) && strtoupper($itemData['tax_name']) === 'PPN') {
                        $tax = Tax::firstOrCreate(
                            ['name' => 'PPN'],
                            ['rate' => 11]
                        );
                        $taxId = $tax->id;
                        $taxRate = $tax->rate;
                        $taxName = $tax->name;
                    }

                    // Calculate Values
                    $quantity = $itemData['quantity'];
                    $unitPrice = $itemData['unit_price'];
                    $subTotal = $quantity * $unitPrice;

                    $discountAmount = $itemData['discount_amount'];
                    $discountPercent = $itemData['discount_percent'];

                    if ($discountAmount > 0 && $discountPercent == 0) {
                        if ($subTotal > 0) {
                            $discountPercent = ($discountAmount / $subTotal) * 100;
                        }
                    } elseif ($discountPercent > 0 && $discountAmount == 0) {
                        $discountAmount = $subTotal * ($discountPercent / 100);
                    }

                    $taxableAmount = $subTotal - $discountAmount;
                    $taxAmount = 0;

                    if ($data['tax_inclusive'] && $taxId) {
                        $baseAmount = $taxableAmount / (1 + ($taxRate / 100));
                        $taxAmount = $taxableAmount - $baseAmount;
                    } elseif ($taxId) {
                        $taxAmount = $taxableAmount * ($taxRate / 100);
                    }

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $order->id,
                        'product_id' => $product->id,
                        'description' => $itemData['description'],
                        'unit_id' => $unitId,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'discount_percent' => $discountPercent,
                        'tax_id' => $taxId,
                        'tax_name' => $taxName,
                        'tax_amount' => $taxAmount,
                        'total_price' => $taxableAmount,
                    ]);
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error("Failed to process order $orderNumber: " . $e->getMessage());
                Log::error("Import failed for $orderNumber: " . $e->getMessage());
            }
        }

        $this->command->info("Import process completed.");
    }

    private function parseNumber($value)
    {
        if (empty($value))
            return 0;
        return (float) $value;
    }

    private function parseDate($value)
    {
        if (empty($value))
            return null;
        try {
            return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
