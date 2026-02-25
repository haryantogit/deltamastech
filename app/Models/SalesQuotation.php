<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesQuotation extends Model
{
    use HasFactory, \App\Traits\HasAutomaticNumbering;

    public function getNumberingSettingKey(): ?string
    {
        return 'sales_quotation';
    }

    protected $fillable = [
        'number',
        'reference',
        'date',
        'expiry_date',
        'contact_id',
        'payment_term_id',
        'status',
        'tax_inclusive',
        'sub_total',
        'discount_amount',
        'shipping_cost',
        'other_cost',
        'total_amount',
        'notes',
        'shipping_method_id',
        'shipping_date',
        'tracking_number',
    ];

    protected $casts = [
        'date' => 'date',
        'expiry_date' => 'date',
        'shipping_date' => 'date',
        'tax_inclusive' => 'boolean',
        'sub_total' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function contact(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SalesQuotationItem::class);
    }

    public function paymentTerm(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function shippingMethod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'sales_quotation_tags', 'sales_quotation_id', 'tag_id');
    }
}
