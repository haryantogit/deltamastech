<?php

namespace App\Observers;

use App\Models\WarehouseTransfer;

class WarehouseTransferObserver
{
    /**
     * Handle the WarehouseTransfer "created" event.
     */
    public function created(WarehouseTransfer $warehouseTransfer): void
    {
        // Items are usually not yet available in 'created' if using relationship save.
        // But in Filament, it might be different.
        // Let's use 'saved' and check if it was just created.
    }

    /**
     * Handle the WarehouseTransfer "saved" event.
     */
    public function saved(WarehouseTransfer $warehouseTransfer): void
    {
        // Handled by WarehouseTransferItemObserver
    }

    public function deleting(WarehouseTransfer $warehouseTransfer): void
    {
        foreach ($warehouseTransfer->items as $item) {
            // Reverse: IN to Source (Add back what was taken out)
            \App\Services\StockService::updateStock(
                $item->product_id,
                $warehouseTransfer->from_warehouse_id,
                (float) $item->quantity,
                'transfer',
                \App\Models\WarehouseTransfer::class,
                $warehouseTransfer->id,
                'Reversal: Hapus Transfer (In to Source)'
            );

            // Reverse: OUT from Destination (Take back what was put in)
            \App\Services\StockService::updateStock(
                $item->product_id,
                $warehouseTransfer->to_warehouse_id,
                -(float) $item->quantity,
                'transfer',
                \App\Models\WarehouseTransfer::class,
                $warehouseTransfer->id,
                'Reversal: Hapus Transfer (Out from Dest)'
            );
        }
    }

    /**
     * Handle the WarehouseTransfer "deleted" event.
     */
    public function deleted(WarehouseTransfer $warehouseTransfer): void
    {
        //
    }

    /**
     * Handle the WarehouseTransfer "restored" event.
     */
    public function restored(WarehouseTransfer $warehouseTransfer): void
    {
        //
    }

    /**
     * Handle the WarehouseTransfer "force deleted" event.
     */
    public function forceDeleted(WarehouseTransfer $warehouseTransfer): void
    {
        //
    }
}
