<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtItem extends Model
{
    protected $fillable = [
        'debt_id',
        'account_id',
        'description',
        'quantity',
        'unit_price',
        'total_price'
    ];

    public function debt()
    {
        return $this->belongsTo(Debt::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
