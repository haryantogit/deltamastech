<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderCost extends Model
{
    protected $fillable = [
        'production_order_id',
        'account_id',
        'unit_amount',
        'multiplier',
        'amount',
        'description',
    ];

    protected $casts = [
        'unit_amount' => 'decimal:2',
        'multiplier' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
