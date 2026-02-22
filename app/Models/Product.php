<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'sku',
        'type',
        'is_active',
        'buy_price',
        'sell_price',
        'cost_of_goods',
        'stock',
        'min_stock',
        'unit_name',
        'unit_id',
        'category_id',
        'can_be_purchased',
        'can_be_sold',
        'track_inventory',
        'purchase_account_id',
        'sales_account_id',
        'inventory_account_id',
        'purchase_tax_id',
        'sales_tax_id',
        'wholesale_prices',
        'image',
        'is_fixed_asset',
        'purchase_date',
        'purchase_price',
        'useful_life_years',
        'salvage_value',
        'depreciation_method',
        'asset_account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'credit_account_id',
        'reference',
        'has_depreciation',
        'depreciation_rate',
        'useful_life_months',
        'depreciation_start_date',
        'accumulated_depreciation_value',
        'cost_limit',
        'status',
        'purchase_invoice_id',
        'disposal_date',
        'disposal_price',
    ];

    protected $casts = [
        'purchase_price_includes_tax' => 'boolean',
        'sales_price_includes_tax' => 'boolean',
        'wholesale_prices' => 'array',
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'stock' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'cost_of_goods' => 'decimal:2',
        'can_be_purchased' => 'boolean',
        'can_be_sold' => 'boolean',
        'track_inventory' => 'boolean',
        'is_active' => 'boolean',
        'image' => 'array',
        'is_fixed_asset' => 'boolean',
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'has_depreciation' => 'boolean',
        'depreciation_rate' => 'decimal:2',
        'depreciation_start_date' => 'date',
        'accumulated_depreciation_value' => 'decimal:2',
        'cost_limit' => 'decimal:2',
        'disposal_date' => 'date',
        'disposal_price' => 'decimal:2',
    ];

    public function unit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function purchaseAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class, 'purchase_account_id');
    }

    public function salesAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class, 'sales_account_id');
    }

    public function inventoryAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }

    public function assetAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function accumulatedDepreciationAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    public function creditAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }

    public function purchaseInvoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }




    protected static function booted()
    {
        static::creating(function ($product) {
            if (empty($product->slug) && !empty($product->name)) {
                $product->slug = \Illuminate\Support\Str::slug($product->name);
            }
            // Auto-set status for Fixed Assets based on purchase invoice
            if ($product->is_fixed_asset) {
                if (!empty($product->purchase_invoice_id)) {
                    $product->status = 'registered';
                } elseif (empty($product->status)) {
                    $product->status = 'draft';
                }
            }
            // Sync HPP with buy_price only for standard/service products
            if (in_array($product->type, ['standard', 'service'])) {
                $product->cost_of_goods = $product->buy_price;
            }
        });

        static::updating(function ($product) {
            if (empty($product->slug) && !empty($product->name)) {
                $product->slug = \Illuminate\Support\Str::slug($product->name);
            }
            // Auto-set status for Fixed Assets based on purchase invoice
            if ($product->is_fixed_asset) {
                if (!empty($product->purchase_invoice_id)) {
                    $product->status = 'registered';
                }
            }
            // Sync HPP with buy_price only for standard/service products
            if (in_array($product->type, ['standard', 'service'])) {
                $product->cost_of_goods = $product->buy_price;
            }
        });
    }

    /**
     * The materials that belong to the product (if it is a manufacturing product).
     */
    public function materials(): BelongsToMany
    {
        // product_id is the parent (manufactured item), material_id is the child (ingredient)
        return $this->belongsToMany(Product::class, 'product_materials', 'product_id', 'material_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * The items that belong to the bundle (if it is a bundle product).
     */
    public function bundleItems(): BelongsToMany
    {
        // product_id is the bundle, item_id is the component
        return $this->belongsToMany(Product::class, 'product_bundles', 'product_id', 'item_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * The bundles that this product belongs to (inverse of bundleItems).
     */
    public function usedInBundles(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_bundles', 'item_id', 'product_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * The manufacturing products that use this product as material (inverse of materials).
     */
    public function usedInManufacturing(): BelongsToMany
    {
        // material_id is this product, product_id is the manufacturing product
        return $this->belongsToMany(Product::class, 'product_materials', 'material_id', 'product_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * HasMany for Repeater support (Materials)
     */
    public function productMaterials(): HasMany
    {
        return $this->hasMany(ProductMaterial::class, 'product_id');
    }

    /**
     * HasMany for Repeater support (Bundles)
     */
    public function productBundles(): HasMany
    {
        return $this->hasMany(ProductBundle::class, 'product_id');
    }

    public function salesInvoiceItems(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function purchaseInvoiceItems(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockAdjustmentItems(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * HasMany for production overhead costs (manufacturing products)
     */
    public function productionCosts(): HasMany
    {
        return $this->hasMany(ProductionCost::class);
    }

    /**
     * HasMany for product variants (variant products)
     */
    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * HasMany for unit conversions (e.g., 1 Box = 12 Pcs)
     */
    public function productUnits(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function fixedAssetDepreciations(): HasMany
    {
        return $this->hasMany(FixedAssetDepreciation::class, 'fixed_asset_id');
    }

    public function fixedAssetUpgrades(): HasMany
    {
        return $this->hasMany(FixedAssetUpgrade::class, 'fixed_asset_id');
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get stock quantity for a specific warehouse or total stock if no warehouse provided.
     */
    public function getStockForWarehouse($warehouseId = null): float
    {
        if (!$this->track_inventory) {
            return 0;
        }

        $query = $this->stocks();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (float) $query->sum('quantity');
    }
}
