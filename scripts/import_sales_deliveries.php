<?php

use App\Models\Contact;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesDelivery;
use App\Models\SalesDeliveryItem;
use App\Models\Warehouse;
use App\Models\Tag;
use App\Models\ShippingMethod;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

function importSalesDeliveries($filePath)
{
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        return;
    }

    $handle = fopen($filePath, 'r');
    $header = fgetcsv($handle);

    // Map headers to indices
    $colMap = array_flip($header);

    $rowCount = 0;
    $deliveryCount = 0;
    $updateCount = 0;
    $itemCount = 0;

    while (($data = fgetcsv($handle)) !== FALSE) {
        $rowCount++;

        $deliveryNumber = $data[$colMap['Nomor Pengiriman']] ?? null;
        if (empty($deliveryNumber))
            continue;

        $customerName = $data[$colMap['Nama Kontak']] ?? null;
        $orderNumber = $data[$colMap['Nomor Pesanan']] ?? null;
        $dateRaw = $data[$colMap['Tanggal Pengiriman']] ?? null;
        $warehouseName = $data[$colMap['Gudang']] ?? 'Unassigned';
        $reference = $data[$colMap['Referensi']] ?? null;
        $shippingMethodName = $data[$colMap['Ekspedisi']] ?? null;
        $trackingNumber = $data[$colMap['No. Resi']] ?? null;
        $shippingCost = (float) (preg_replace('/[^0-9.]/', '', $data[$colMap['Biaya Pengiriman']] ?? 0));
        $statusRaw = $data[$colMap['Status']] ?? 'Open';
        $tagsRaw = $data[$colMap['Tag']] ?? null;
        $address = $data[$colMap['Alamat']] ?? null;

        // Item fields
        $sku = $data[$colMap['Kode Produk (SKU)']] ?? null;
        $description = $data[$colMap['Deskripsi Produk']] ?? null;
        $quantity = (float) (preg_replace('/[^0-9.]/', '', $data[$colMap['Kuantitas']] ?? 0));

        if (empty($sku))
            continue;

        DB::beginTransaction();
        try {
            // 1. Get/Create Customer
            $customer = Contact::firstOrCreate(
                ['name' => $customerName],
                ['type' => 'customer']
            );

            // 2. Get Warehouse
            $warehouse = Warehouse::where('name', $warehouseName)->first();
            if (!$warehouse) {
                $warehouse = Warehouse::create(['name' => $warehouseName]);
            }

            // 3. Parse Dates
            $date = null;
            if ($dateRaw) {
                try {
                    $date = Carbon::createFromFormat('d/m/Y', $dateRaw);
                } catch (\Exception $e) {
                    $date = now();
                }
            } else {
                $date = now();
            }

            // 4. Find Sales Order if exists
            $salesOrderId = null;
            if ($orderNumber) {
                $salesOrder = SalesOrder::where('number', $orderNumber)->first();
                if ($salesOrder) {
                    $salesOrderId = $salesOrder->id;
                }
            }

            // 5. Get/Create SalesDelivery
            $salesDelivery = SalesDelivery::where('number', $deliveryNumber)->first();
            if (!$salesDelivery) {
                $salesDelivery = SalesDelivery::create([
                    'number' => $deliveryNumber,
                    'reference' => $reference,
                    'date' => $date,
                    'sales_order_id' => $salesOrderId,
                    'customer_id' => $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'shipping_cost' => $shippingCost,
                    'status' => 'shipped',
                    'tracking_number' => $trackingNumber,
                    'address' => $address,
                ]);
                $deliveryCount++;
            } else {
                if (!empty($reference)) {
                    $salesDelivery->update(['reference' => $reference]);
                    $updateCount++;
                }
            }

            // Handle Shipping Method if exists
            if ($shippingMethodName) {
                $sm = ShippingMethod::firstOrCreate(['name' => $shippingMethodName]);
                $salesDelivery->update(['shipping_method_id' => $sm->id]);
            }

            // Handle Tags
            if ($tagsRaw) {
                $tagNames = explode(',', $tagsRaw);
                foreach ($tagNames as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => trim($tagName)]);
                    $salesDelivery->tags()->syncWithoutDetaching([$tag->id]);
                }
            }

            // 6. Get Product
            $product = Product::where('sku', $sku)->first();
            if (!$product) {
                $product = Product::create([
                    'name' => $data[$colMap['Nama Produk']] ?? $sku,
                    'sku' => $sku,
                    'type' => 'storable',
                    'unit_id' => 1,
                ]);
            }

            // 7. Create SalesDeliveryItem only if not exists
            $existingItem = SalesDeliveryItem::where('sales_delivery_id', $salesDelivery->id)
                ->where('product_id', $product->id)
                ->where('quantity', $quantity)
                ->first();

            if (!$existingItem) {
                SalesDeliveryItem::create([
                    'sales_delivery_id' => $salesDelivery->id,
                    'product_id' => $product->id,
                    'description' => $description,
                    'unit_id' => $product->unit_id,
                    'quantity' => $quantity,
                ]);
                $itemCount++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            echo "Error processing row $rowCount ($deliveryNumber): " . $e->getMessage() . "\n";
        }
    }

    fclose($handle);
    echo "Processed $filePath: Created $deliveryCount, Updated $updateCount deliveries and $itemCount items.\n";
}

$files = [
    'd:/Program Receh/kledo/data/pengiriman_29-Jan-2026_halaman-1.csv',
    'd:/Program Receh/kledo/data/pengiriman_29-Jan-2026_halaman-2.csv',
    'd:/Program Receh/kledo/data/pengiriman_29-Jan-2026_halaman-3.csv',
];

foreach ($files as $file) {
    importSalesDeliveries($file);
}
