<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'type',
        'is_deduction',
        'sales_account_id',
        'purchase_account_id'
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_deduction' => 'boolean',
    ];

    public function salesAccount()
    {
        return $this->belongsTo(Account::class, 'sales_account_id');
    }

    public function purchaseAccount()
    {
        return $this->belongsTo(Account::class, 'purchase_account_id');
    }
}
