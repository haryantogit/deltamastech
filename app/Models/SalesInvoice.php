<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SalesInvoice extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    protected $fillable = [
        'contact_id',
        'sales_order_id',
        'invoice_number',
        'reference',
        'transaction_date',
        'due_date',
        'status',
        'payment_status',
        'total_amount',
        'shipping_method_id',
        'warehouse_id',
        'account_id',
        'notes',
        'shipping_date',
        'sub_total',
        'discount_total',
        'shipping_cost',
        'tax_inclusive',
        'total_tax',
        'withholding_amount',
        'down_payment',
        'other_cost',
        'balance_due',
        'payment_term_id',
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
        'transaction_date' => 'date',
        'due_date' => 'date',
        'shipping_date' => 'date',
        'total_amount' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_inclusive' => 'boolean',
        'total_tax' => 'decimal:2',
        'withholding_amount' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'attachments' => 'array',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }
    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class, 'invoice_number', 'invoice_number');
    }

    public function getBalanceDueAttribute(): float
    {
        if ($this->status === 'paid') {
            return 0;
        }

        $receivable = \App\Models\Receivable::where('invoice_number', $this->invoice_number)->first();
        $totalPaid = ($receivable ? $receivable->payments()->sum('amount') : 0) + ($this->down_payment ?? 0);
        $balance = (float) ($this->total_amount - $totalPaid - ($this->withholding_amount ?? 0));

        return max(0, $balance);
    }
}
