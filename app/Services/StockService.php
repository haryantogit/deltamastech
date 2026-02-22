<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Update stock and record movement.
     * 
     * @param int $productId
     * @param int $warehouseId
     * @param float $quantity Positive for IN, Negative for OUT
     * @param string $type sales, purchase, transfer, adjustment
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @param string|null $description
     */
    public static function updateStock($productId, $warehouseId, $quantity, $type, $referenceType = null, $referenceId = null, $description = null)
    {
        DB::transaction(function () use ($productId, $warehouseId, $quantity, $type, $referenceType, $referenceId, $description) {
            // 1. Create Stock Movement
            StockMovement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => auth()->id(),
                'description' => substr($description, 0, 255),
            ]);

            // 2. Update Per-Warehouse Stock
            $stock = Stock::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                ['quantity' => 0]
            );
            $stock->increment('quantity', $quantity);

            // 3. Update Global Product Stock
            Product::where('id', $productId)->increment('stock', $quantity);
        });
    }
}
