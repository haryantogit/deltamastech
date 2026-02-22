<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseFunction;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DummyPurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::first();
        $products = Product::inRandomOrder()->take(5)->get();
        $suppliers = Contact::where('type', 'vendor')->orWhere('type', 'both')->inRandomOrder()->take(3)->get();

        if ($suppliers->isEmpty()) {
            $suppliers = Contact::factory()->count(3)->create(['type' => 'vendor']);
        }

        if ($products->isEmpty()) {
            // Create dummy products if none exist
            // Assuming Product factory exists
        }

        $statuses = ['paid', 'pending', 'overdue'];

        foreach (range(1, 15) as $i) {
            $supplier = $suppliers->random();
            $status = $statuses[array_rand($statuses)];
            $date = Carbon::now()->subDays(rand(0, 20));
            $dueDate = $date->copy()->addDays(30);

            if ($status === 'overdue') {
                $date = Carbon::now()->subDays(40);
                $dueDate = $date->copy()->addDays(30); // Due 10 days ago
                $status = 'pending'; // Overdue is a state, not necessarily a status enum in all systems, but here let's assume 'pending' + date logic
            }

            $invoice = PurchaseInvoice::create([
                'number' => 'PINV-' . date('Ymd') . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'date' => $date,
                'due_date' => $dueDate,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id ?? 1,
                'status' => $status === 'paid' ? 'paid' : 'pending',
                'payment_status' => $status === 'paid' ? 'paid' : 'unpaid',
                'total_amount' => 0, // Will update
                'tax_inclusive' => false,
                'sub_total' => 0,
                'discount_amount' => 0,
                'shipping_cost' => 0,
                'other_cost' => 0,
                'balance_due' => 0, // accessor, but maybe needed for query if column exists
            ]);

            $total = 0;
            foreach ($products->random(rand(1, 3)) as $product) {
                $qty = rand(1, 10);
                $price = $product->purchase_price > 0 ? $product->purchase_price : rand(10000, 100000);
                $subtotal = $qty * $price;

                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total_price' => $subtotal,
                    'description' => $product->name,
                ]);
                $total += $subtotal;
            }

            $invoice->update([
                'sub_total' => $total,
                'total_amount' => $total,
                'balance_due' => $status === 'paid' ? 0 : $total,
            ]);
        }
    }
}
