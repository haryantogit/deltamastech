<?php

namespace App\Observers;

use App\Models\ManufacturingOrder;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class ManufacturingOrderObserver
{
    /**
     * Handle the ManufacturingOrder "updated" event.
     */
    public function updated(ManufacturingOrder $manufacturingOrder): void
    {
        // Only trigger when status changes to 'Approved'
        if ($manufacturingOrder->isDirty('status') && $manufacturingOrder->status === 'Approved') {
            $this->handleProduction($manufacturingOrder);
        }
    }

    /**
     * Handle the ManufacturingOrder "created" event.
     */
    public function created(ManufacturingOrder $manufacturingOrder): void
    {
        // If created directly as Approved (unlikely in UI but possible via API/Seeder)
        if ($manufacturingOrder->status === 'Approved') {
            $this->handleProduction($manufacturingOrder);
        }
    }

    /**
     * Deduct materials and increase finished good stock.
     */
    protected function handleProduction(ManufacturingOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $productionQty = (float) $order->quantity;
            $finishedGood = $order->product;

            // 1. Deduct Materials
            foreach ($finishedGood->materials as $material) {
                $neededQty = $material->pivot->quantity_needed * $productionQty;

                \App\Services\StockService::updateStock(
                    $material->id,
                    $order->warehouse_id,
                    -abs($neededQty), // Negative for OUT
                    'manufacturing',
                    ManufacturingOrder::class,
                    $order->id
                );
            }

            // 2. Add Finished Good
            \App\Services\StockService::updateStock(
                $finishedGood->id,
                $order->warehouse_id,
                abs($productionQty), // Positive for IN
                'manufacturing',
                ManufacturingOrder::class,
                $order->id
            );
        });
    }
}
