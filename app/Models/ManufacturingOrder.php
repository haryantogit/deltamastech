<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturingOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_date',
        'product_id',
        'warehouse_id',
        'quantity',
        'status',
        'notes',
    ];

    /**
     * The warehouse where materials are taken and finished goods are stored.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * The product (finished good) being manufactured.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
