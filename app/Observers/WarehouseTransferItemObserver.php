<?php

namespace App\Observers;

use App\Models\WarehouseTransferItem;
use App\Services\StockService;

class WarehouseTransferItemObserver
{
    public function created(WarehouseTransferItem $item): void
    {
        $transfer = $item->transfer;
        if (!$transfer)
            return;

        // OUT from Source
        if ($transfer->from_warehouse_id) {
            StockService::updateStock(
                $item->product_id,
                $transfer->from_warehouse_id,
                -(float) $item->quantity,
                'transfer',
                WarehouseTransferItem::class,
                $item->id
            );
        } else {
            // Unassigned Source: Manually Decrement Product Stock and Record Movement
            \Illuminate\Support\Facades\DB::transaction(function () use ($item) {
                // 1. Movement
                \App\Models\StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => null,
                    'quantity' => -(float) $item->quantity,
                    'type' => 'transfer',
                    'reference_type' => WarehouseTransferItem::class,
                    'reference_id' => $item->id,
                    'user_id' => auth()->id(),
                    'description' => 'Transfer Out from Unassigned',
                ]);

                // 2. Decrement Product Stock (to balance the increment at destination)
                \App\Models\Product::where('id', $item->product_id)->decrement('stock', $item->quantity);
            });
        }

        // IN to Destination
        StockService::updateStock(
            $item->product_id,
            $transfer->to_warehouse_id,
            (float) $item->quantity,
            'transfer',
            WarehouseTransferItem::class,
            $item->id
        );
    }

    public function updated(WarehouseTransferItem $item): void
    {
        $transfer = $item->transfer;
        if (!$transfer)
            return;

        $delta = (float) $item->quantity - (float) $item->getOriginal('quantity');
        if ($delta == 0)
            return;

        // OUT from Source
        if ($transfer->from_warehouse_id) {
            StockService::updateStock(
                $item->product_id,
                $transfer->from_warehouse_id,
                -$delta,
                'transfer',
                WarehouseTransferItem::class,
                $item->id
            );
        } else {
            // Unassigned Source Update
            \Illuminate\Support\Facades\DB::transaction(function () use ($item, $delta) {
                \App\Models\StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => null,
                    'quantity' => -$delta,
                    'type' => 'transfer',
                    'reference_type' => WarehouseTransferItem::class,
                    'reference_id' => $item->id,
                    'user_id' => auth()->id(),
                    'description' => 'Update Transfer Out (Unassigned)',
                ]);

                \App\Models\Product::where('id', $item->product_id)->decrement('stock', $delta);
            });
        }

        // IN to Destination
        StockService::updateStock(
            $item->product_id,
            $transfer->to_warehouse_id,
            $delta,
            'transfer',
            WarehouseTransferItem::class,
            $item->id
        );
    }

    public function deleted(WarehouseTransferItem $item): void
    {
        $transfer = $item->transfer;
        if (!$transfer)
            return;

        // Reverse: IN to Source
        if ($transfer->from_warehouse_id) {
            StockService::updateStock(
                $item->product_id,
                $transfer->from_warehouse_id,
                (float) $item->quantity, // Add back to source
                'transfer',
                \App\Models\WarehouseTransfer::class,
                $transfer->id,
                'Reversal Transfer Keluar'
            );
        } else {
            // Reverse Unassigned Source
            \Illuminate\Support\Facades\DB::transaction(function () use ($item) {
                \App\Models\StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => null,
                    'quantity' => (float) $item->quantity, // Add back
                    'type' => 'transfer',
                    'reference_type' => WarehouseTransferItem::class,
                    'reference_id' => $item->id,
                    'user_id' => auth()->id(),
                    'description' => 'Reversal Transfer Out (Unassigned)',
                ]);

                \App\Models\Product::where('id', $item->product_id)->increment('stock', $item->quantity);
            });
        }

        // Reverse: OUT from Destination
        StockService::updateStock(
            $item->product_id,
            $transfer->to_warehouse_id,
            -(float) $item->quantity, // Remove from destination
            'transfer',
            \App\Models\WarehouseTransfer::class,
            $transfer->id,
            'Reversal Transfer Masuk'
        );
    }
}
