<?php

namespace App\Observers;

use App\Models\SalesDelivery;
use App\Services\StockService;

class SalesDeliveryObserver
{
    /**
     * Handle the SalesDelivery "created" event.
     */
    public function created(SalesDelivery $salesDelivery): void
    {
        if (in_array($salesDelivery->status, ['shipped', 'delivered'])) {
            $this->processStockOut($salesDelivery);
        }

        $this->updateSalesOrderStatus($salesDelivery);
    }

    /**
     * Handle the SalesDelivery "updated" event.
     */
    public function updated(SalesDelivery $salesDelivery): void
    {
        // Only process if status changed to 'shipped' or 'delivered'
        if ($salesDelivery->isDirty('status') && in_array($salesDelivery->status, ['shipped', 'delivered'])) {
            // Check if we already processed this (e.g. from draft to shipped)
            // For now, we assume if it's shipped/delivered, we want the stock out
            // To be more robust, we would check if a StockMovement already exists for this reference
            $this->processStockOut($salesDelivery);
        }

        $this->updateSalesOrderStatus($salesDelivery);
    }

    protected function processStockOut(SalesDelivery $salesDelivery): void
    {
        // Ensure items are loaded
        $salesDelivery->load('items');

        foreach ($salesDelivery->items as $item) {
            // Negative Quantity for Sales (Stock Out)
            $quantity = -1 * abs($item->quantity);

            StockService::updateStock(
                productId: $item->product_id,
                warehouseId: $salesDelivery->warehouse_id ?? 1,
                quantity: $quantity,
                type: 'sales',
                referenceType: SalesDelivery::class,
                referenceId: $salesDelivery->id,
                description: "Pengiriman Penjualan #{$salesDelivery->number}" . ($salesDelivery->salesOrder ? " (SO #{$salesDelivery->salesOrder->number})" : "")
            );
        }
    }

    protected function updateSalesOrderStatus(SalesDelivery $salesDelivery): void
    {
        // Update SalesOrder status to delivered if not already completed/cancelled
        $order = $salesDelivery->salesOrder;
        if ($order && !in_array($order->status, ['completed', 'cancelled'])) {
            $order->status = 'delivered';
            $order->save();
        }
    }
}
