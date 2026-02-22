<?php

namespace App\Observers;

use App\Models\StockAdjustmentItem;
use App\Services\StockService;

class StockAdjustmentItemObserver
{
    public function created(StockAdjustmentItem $item): void
    {
        $adjustment = $item->adjustment;
        if (!$adjustment)
            return;

        StockService::updateStock(
            $item->product_id,
            $adjustment->warehouse_id,
            (float) $item->quantity,
            'adjustment',
            StockAdjustmentItem::class,
            $item->id
        );
    }

    public function updated(StockAdjustmentItem $item): void
    {
        $adjustment = $item->adjustment;
        if (!$adjustment)
            return;

        $delta = (float) $item->quantity - (float) $item->getOriginal('quantity');
        if ($delta == 0)
            return;

        StockService::updateStock(
            $item->product_id,
            $adjustment->warehouse_id,
            $delta,
            'adjustment',
            StockAdjustmentItem::class,
            $item->id
        );
    }

    public function deleted(StockAdjustmentItem $item): void
    {
        $adjustment = $item->adjustment;
        if (!$adjustment)
            return;

        // Reverse the adjustment
        StockService::updateStock(
            $item->product_id,
            $adjustment->warehouse_id,
            -(float) $item->quantity, // Reverse the quantity
            'adjustment',
            StockAdjustmentItem::class,
            $item->id,
            ($adjustment->notes ?? 'Penyesuaian stok') . ' (Reversal)'
        );
    }
}
