<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    use \App\Traits\HasAutomaticNumbering;

    public function getNumberingSettingKey(): ?string
    {
        return 'production_order';
    }
    protected $fillable = [
        'number',
        'transaction_date',
        'product_id',
        'warehouse_id',
        'quantity',
        'status',
        'total_cost',
        'notes',
        'tag',
        'warehouse_sync',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'quantity' => 'decimal:4',
        'total_cost' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }

    public function costs(): HasMany
    {
        return $this->hasMany(ProductionOrderCost::class);
    }
}
