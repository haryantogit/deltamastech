<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'supplier_id',
        'date',
        'due_date',
        'payment_term_id',
        'warehouse_id',
        'reference',
        'shipping_date',
        'shipping_method_id',
        'tracking_number',
        'status',
        'notes',
        'sub_total',
        'discount_amount',
        'shipping_cost',
        'other_cost',
        'tax_amount',
        'tax_inclusive',
        'total_amount',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'shipping_date' => 'date',
        'tax_inclusive' => 'boolean',
        'sub_total' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseQuoteItem::class);
    }
}
