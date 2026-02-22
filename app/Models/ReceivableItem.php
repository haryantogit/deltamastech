<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Receivable;
use App\Models\Account;

class ReceivableItem extends Model
{
    protected $fillable = [
        'receivable_id',
        'account_id',
        'description',
        'qty',
        'price',
        'subtotal'
    ];

    public function receivable()
    {
        return $this->belongsTo(Receivable::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
