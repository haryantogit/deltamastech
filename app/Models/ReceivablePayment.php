<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Receivable;
use App\Models\Account;

class ReceivablePayment extends Model
{
    protected $fillable = [
        'receivable_id',
        'number',
        'date',
        'account_id',
        'amount',
        'notes',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
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
