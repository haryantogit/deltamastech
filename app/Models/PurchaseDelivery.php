<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'date',
        'purchase_order_id',
        'supplier_id',
        'warehouse_id',
        'status',
        'shipping_date',
        'shipping_method_id',
        'tracking_number',
        'notes',
        'attachments',
        'reference',
        'shipping_cost',
        'tax_inclusive',
    ];

    protected $casts = [
        'date' => 'date',
        'shipping_date' => 'date',
        'shipping_cost' => 'decimal:2',
        'tax_inclusive' => 'boolean',
        'attachments' => 'array',
    ];

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseDeliveryItem::class);
    }
}
