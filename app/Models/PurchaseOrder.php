<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasAutomaticNumbering;

class PurchaseOrder extends Model
{
    use HasFactory, LogsActivity, HasAutomaticNumbering;

    public function getNumberingSettingKey(): ?string
    {
        return 'purchase_order';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    protected $fillable = [
        'number',
        'date',
        'due_date',
        'supplier_id',
        'payment_term_id',
        'warehouse_id',
        'shipping_date',
        'shipping_method_id',
        'tracking_number',
        'status',
        'tax_inclusive',
        'total_amount',
        'discount_amount',
        'shipping_cost',
        'other_cost',
        'down_payment',
        'notes',
        'reference',
        'attachments',
        'sub_total',
        'tax_amount',
    ];

    public function tags(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function shippingMethod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'shipping_date' => 'date',
        'tax_inclusive' => 'boolean',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'attachments' => 'array',
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

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(PurchaseDelivery::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->invoices()
            ->where(function ($query) {
                $query->where('status', 'paid')
                    ->orWhere('payment_status', 'paid');
            })
            ->sum('total_amount');
    }

    public function getBalanceDueAttribute(): float
    {
        // We use total_amount - paid_amount. 
        // If status is 'paid', force 0 for safety/consistency.
        if ($this->status === 'paid') {
            return 0;
        }
        return (float) ($this->total_amount - $this->paid_amount);
    }
}
