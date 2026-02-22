<?php

namespace App\Filament\Imports;

use App\Models\Contact;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Log;

class SalesOrderImporter extends Importer
{
    protected static ?string $model = SalesOrderItem::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('order_number')
                ->label('*Nomor Pesanan')
                ->requiredMapping()
                ->rules(['required']),

            ImportColumn::make('customer_name')
                ->label('*Nama Kontak')
                ->requiredMapping()
                ->rules(['required']),

            ImportColumn::make('sku')
                ->label('*Kode Produk (SKU)')
                ->requiredMapping()
                ->rules(['required']),

            ImportColumn::make('date')
                ->label('*Tanggal Transaksi (dd/mm/yyyy)')
                ->requiredMapping()
                ->rules(['required']),

            ImportColumn::make('quantity')
                ->label('*Jumlah Produk')
                ->numeric()
                ->rules(['required', 'numeric']),

            ImportColumn::make('unit_price')
                ->label('*Harga Produk')
                ->numeric()
                ->rules(['required', 'numeric']),

            ImportColumn::make('total_amount_order')
                ->label('Total')
                ->numeric()
                ->rules(['nullable']),

            ImportColumn::make('notes')
                ->label('Catatan')
                ->rules(['nullable']),

            ImportColumn::make('shipping_method')
                ->label('Ekspedisi / Pengiriman Kurir')
                ->rules(['nullable']),

            ImportColumn::make('tags_raw')
                ->label('Tag (Beberapa Tag Dipisah Dengan Koma)')
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): ?SalesOrderItem
    {
        $orderNumber = $this->data['order_number'] ?? null;
        $customerName = $this->data['customer_name'] ?? null;
        $sku = $this->data['sku'] ?? null;
        $dateRaw = $this->data['date'] ?? null;
        $totalAmountOrder = $this->data['total_amount_order'] ?? 0;
        $notes = $this->data['notes'] ?? null;

        if (blank($orderNumber) || blank($sku)) {
            return null; // Skip invalid rows
        }

        // 1. Find Customer
        $customer = Contact::firstOrCreate(
            ['name' => $customerName],
            ['type' => 'customer'] // Default basic creation
        );

        // 2. Find Product
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            // Throwing exception might stop import, let's log or skip?
            // User said: Find Product by SKU. If missing, throw Exception "Produk SKU [sku] tidak ditemukan".
            throw new \Exception("Produk SKU [{$sku}] tidak ditemukan");
        }

        // 3. Parse Date
        try {
            $date = Carbon::createFromFormat('d/m/Y', $dateRaw);
        } catch (\Exception $e) {
            $date = now();
        }

        // 4. Find/Create Parent SalesOrder
        $salesOrder = SalesOrder::where('number', $orderNumber)->first();

        // Handle Shipping Method
        $shippingMethodId = null;
        if (!empty($this->data['shipping_method'])) {
            $shippingMethodId = \App\Models\ShippingMethod::firstOrCreate(['name' => $this->data['shipping_method']])->id;
        }

        if (!$salesOrder) {
            $salesOrder = SalesOrder::create([
                'number' => $orderNumber,
                'customer_id' => $customer->id,
                'date' => $date,
                'status' => 'confirmed',
                'total_amount' => $this->cleanNumber($totalAmountOrder),
                'notes' => $notes,
                'shipping_method_id' => $shippingMethodId,
            ]);
        } else {
            // Update notes or shipping if missing or different
            $updateData = [];
            if ($notes && $salesOrder->notes !== $notes) {
                $updateData['notes'] = $notes;
            }
            if ($shippingMethodId && $salesOrder->shipping_method_id !== $shippingMethodId) {
                $updateData['shipping_method_id'] = $shippingMethodId;
            }

            if (!empty($updateData)) {
                $salesOrder->update($updateData);
            }
        }

        // Handle Tags
        if (!empty($this->data['tags_raw'])) {
            $tagNames = array_map('trim', explode(',', $this->data['tags_raw']));
            $tagIds = [];
            foreach ($tagNames as $tagName) {
                if ($tagName) {
                    $tagIds[] = \App\Models\Tag::firstOrCreate(['name' => $tagName])->id;
                }
            }
            $salesOrder->tags()->syncWithoutDetaching($tagIds);
        }

        // 5. Return SalesOrderItem
        return new SalesOrderItem([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => $this->data['quantity'],
            'unit_price' => $this->data['unit_price'],
            'total_price' => $this->data['quantity'] * $this->data['unit_price'],
        ]);
    }

    protected function cleanNumber($value)
    {
        if (is_string($value)) {
            return (float) str_replace([',', 'Rp', ' '], '', $value);
        }
        return (float) $value;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Sales Order import completed. ' . Number::format($import->successful_rows) . ' items imported.';
    }
}
