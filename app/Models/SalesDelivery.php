<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesDelivery extends Model
{
    use \App\Traits\HasAutomaticNumbering;
    public function tags(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function getNumberingSettingKey(): ?string
    {
        return 'sales_delivery';
    }

    protected $fillable = [
        'number',
        'reference',
        'date',
        'sales_order_id',
        'customer_id',
        'warehouse_id',
        'shipping_method_id',
        'shipping_cost',
        'status',
        'courier',
        'tracking_number',
        'address',
        'notes',
        'attachments',
    ];

    protected $casts = [
        'date' => 'date',
        'attachments' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesDeliveryItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public static function generateNumber(): string
    {
        $prefix = 'SD/';
        $lastOrder = self::where('number', 'like', $prefix . '%')
            ->orderByRaw('LENGTH(number) DESC')
            ->orderBy('number', 'desc')
            ->first();

        $number = 1;
        if ($lastOrder && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastOrder->number, $matches)) {
            $number = intval($matches[1]) + 1;
        }

        do {
            $code = $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
            $exists = self::where('number', $code)->exists();
            if ($exists) {
                $number++;
            }
        } while ($exists);

        return $code;
    }
}
