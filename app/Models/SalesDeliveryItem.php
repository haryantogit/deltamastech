<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesDeliveryItem extends Model
{
    protected $fillable = [
        'sales_delivery_id',
        'product_id',
        'description',
        'unit_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(SalesDelivery::class, 'sales_delivery_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
