<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseInvoice extends Model
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
        'purchase_order_id',
        'supplier_id',
        'date',
        'due_date',
        'status',
        'payment_status',
        'sub_total', // Added
        'total_amount',
        'shipping_method_id',
        'tracking_number',
        'shipping_date',
        'warehouse_id',
        'account_id',
        'payment_term_id', // Added
        'reference', // Added
        'tax_inclusive',
        'discount_amount',
        'shipping_cost',
        'other_cost',
        'tax_amount', // Added
        'withholding_amount', // Added
        'down_payment',
        'notes',
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

    public function paymentTerm(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'shipping_date' => 'date',
        'tax_inclusive' => 'boolean',
        'sub_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2', // Added
        'withholding_amount' => 'decimal:2', // Added
        'down_payment' => 'decimal:2',
        'attachments' => 'array',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function deliveries()
    {
        return $this->hasMany(PurchaseDelivery::class, 'purchase_order_id', 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function getBalanceDueAttribute(): float
    {
        if ($this->payment_status === 'paid' || $this->status === 'paid') {
            return 0;
        }

        $debt = \App\Models\Debt::where('reference', $this->number)->first();
        $totalPaid = ($debt ? $debt->payments()->sum('amount') : 0) + ($this->down_payment ?? 0);
        $balance = (float) ($this->total_amount - $totalPaid - ($this->withholding_amount ?? 0));

        return max(0, $balance);
    }
}
