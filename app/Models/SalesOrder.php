<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SalesOrder extends Model
{
    use HasFactory, LogsActivity;

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
        'customer_id',
        'payment_term_id',
        'warehouse_id',
        'sales_quotation_id',
        'status',
        'tax_inclusive',
        'sub_total',
        'discount_amount',
        'shipping_cost',
        'other_cost',
        'total_amount',
        'down_payment',
        'balance_due',
        'shipping_date',
        'shipping_method_id',
        'tracking_number',
        'notes',
        'reference',
        'attachments',
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
        'sub_total' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'attachments' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id');
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
        return $this->hasMany(SalesOrderItem::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class, 'sales_quotation_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(SalesDelivery::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }
}
