<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseDeliveryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_delivery_id',
        'product_id',
        'unit_id',
        'description',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(PurchaseDelivery::class, 'purchase_delivery_id');
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
