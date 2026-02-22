<?php

namespace App\Observers;

use App\Models\StockAdjustment;

class StockAdjustmentObserver
{
    /**
     * Handle the StockAdjustment "saved" event.
     */
    public function saved(StockAdjustment $stockAdjustment): void
    {
        // Handled by StockAdjustmentItemObserver
    }

    public function deleting(StockAdjustment $stockAdjustment): void
    {
        foreach ($stockAdjustment->items as $item) {
            // Reverse: Subtract the quantity that was adjusted
            \App\Services\StockService::updateStock(
                $item->product_id,
                $stockAdjustment->warehouse_id,
                -(float) $item->quantity,
                'adjustment',
                \App\Models\StockAdjustment::class,
                $stockAdjustment->id,
                'Reversal: Hapus Penyesuaian Stok'
            );
        }
    }

    /**
     * Handle the StockAdjustment "deleted" event.
     */
    public function deleted(StockAdjustment $stockAdjustment): void
    {
        //
    }

    /**
     * Handle the StockAdjustment "restored" event.
     */
    public function restored(StockAdjustment $stockAdjustment): void
    {
        //
    }

    /**
     * Handle the StockAdjustment "force deleted" event.
     */
    public function forceDeleted(StockAdjustment $stockAdjustment): void
    {
        //
    }
}
