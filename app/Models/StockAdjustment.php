<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use HasFactory, \App\Traits\HasAutomaticNumbering;

    public function getNumberingSettingKey(): ?string
    {
        return 'stock_adjustment';
    }

    protected $fillable = [
        'number',
        'date',
        'warehouse_id',
        'reason',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    protected static function booted()
    {
        static::saved(function (StockAdjustment $adjustment) {
            // Usually, adjustments would update total stock. 
            // For now, let's implement the logic to update 'stocks' table per warehouse.
            // This hook will be called after items are saved if we use relationship saveMany.
            // However, in Filament Repeater, items might be saved separately.
            // A more robust way is to use observers or handle it in the service/importer.
        });
    }
}
