<?php

namespace App\Observers;

use App\Models\PurchaseDelivery;
use App\Models\PurchaseOrder;
use App\Services\StockService;

class PurchaseDeliveryObserver
{
    /**
     * Handle the PurchaseDelivery "created" event.
     */
    public function created(PurchaseDelivery $purchaseDelivery): void
    {
        if ($purchaseDelivery->status === 'received') {
            $this->processReceived($purchaseDelivery);
        }
    }

    /**
     * Handle the PurchaseDelivery "updated" event.
     */
    public function updated(PurchaseDelivery $purchaseDelivery): void
    {
        // Only process if status changed to 'received'
        if ($purchaseDelivery->isDirty('status') && $purchaseDelivery->status === 'received') {
            $this->processReceived($purchaseDelivery);
        }
    }

    /**
     * Process stock and PO status for received delivery.
     */
    protected function processReceived(PurchaseDelivery $purchaseDelivery): void
    {
        // Ensure items are loaded
        $purchaseDelivery->load('items');

        foreach ($purchaseDelivery->items as $item) {
            StockService::updateStock(
                productId: $item->product_id,
                warehouseId: $purchaseDelivery->warehouse_id ?? $purchaseDelivery->purchaseOrder?->warehouse_id ?? 1,
                quantity: $item->quantity,
                type: 'purchase',
                referenceType: PurchaseDelivery::class,
                referenceId: $purchaseDelivery->id,
                description: "Penerimaan Barang #{$purchaseDelivery->number}" . ($purchaseDelivery->purchaseOrder ? " (PO #{$purchaseDelivery->purchaseOrder->number})" : "")
            );
        }

        // Update PO Status
        if ($purchaseDelivery->purchase_order_id) {
            $po = $purchaseDelivery->purchaseOrder;
            $po->load(['items', 'deliveries.items']);

            $totalOrdered = $po->items->sum('quantity');
            $totalDelivered = 0;

            foreach ($po->deliveries as $delivery) {
                if ($delivery->status === 'received') {
                    $totalDelivered += $delivery->items->sum('quantity');
                }
            }

            if ($totalDelivered >= $totalOrdered && $totalOrdered > 0) {
                $po->update(['status' => 'received']);
            } elseif ($totalDelivered > 0) {
                $po->update(['status' => 'partial_received']);
            }
        }
    }

    /**
     * Handle the PurchaseDelivery "deleting" event.
     */
    public function deleting(PurchaseDelivery $purchaseDelivery): void
    {
        if ($purchaseDelivery->status !== 'received') {
            return;
        }

        // Ensure items are loaded
        $purchaseDelivery->load('items');

        foreach ($purchaseDelivery->items as $item) {
            StockService::updateStock(
                productId: $item->product_id,
                warehouseId: $purchaseDelivery->warehouse_id ?? $purchaseDelivery->purchaseOrder?->warehouse_id ?? 1,
                quantity: -($item->quantity), // Reverse quantity
                type: 'purchase',
                referenceType: PurchaseDelivery::class,
                referenceId: $purchaseDelivery->id,
                description: "Pembatalan Penerimaan Barang #{$purchaseDelivery->number}"
            );
        }
    }
}
