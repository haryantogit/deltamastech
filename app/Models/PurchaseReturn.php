<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasAutomaticNumbering;

class PurchaseReturn extends Model
{
    use HasFactory, LogsActivity, HasAutomaticNumbering;

    protected $fillable = [
        'number',
        'purchase_invoice_id',
        'supplier_id',
        'warehouse_id',
        'date',
        'reference',
        'notes',
        'tax_inclusive',
        'sub_total',
        'tax_amount',
        'total_amount',
        'status', // draft, confirmed
        'attachments',
    ];

    protected $casts = [
        'date' => 'date',
        'tax_inclusive' => 'boolean',
        'sub_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'attachments' => 'array',
    ];

    public function getNumberingSettingKey(): ?string
    {
        return 'purchase_return';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
