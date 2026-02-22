<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function salesOrders(): MorphToMany
    {
        return $this->morphedByMany(SalesOrder::class, 'taggable');
    }

    public function salesInvoices(): MorphToMany
    {
        return $this->morphedByMany(SalesInvoice::class, 'taggable');
    }

    public function purchaseOrders(): MorphToMany
    {
        return $this->morphedByMany(PurchaseOrder::class, 'taggable');
    }

    public function purchaseInvoices(): MorphToMany
    {
        return $this->morphedByMany(PurchaseInvoice::class, 'taggable');
    }
}
