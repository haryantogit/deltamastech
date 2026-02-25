<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'image',
        'warehouse_id',
        'product_display_type',
        'price_type',
        'price_adjustment',
        'price_unit',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'outlet_user')->withTimestamps();
    }

    public function floors(): HasMany
    {
        return $this->hasMany(OutletFloor::class);
    }

    public function favoriteProducts(): HasMany
    {
        return $this->hasMany(FavoriteProduct::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'outlet_category');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'outlet_product');
    }
}
