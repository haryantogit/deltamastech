<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'barcode',
        'image',
        'buy_price',
        'sell_price',
        'stock',
        'variant_values',
        'is_active',
    ];

    protected $casts = [
        'variant_values' => 'array',
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'stock' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
