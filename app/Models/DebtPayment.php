<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DebtPayment extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
    protected $fillable = [
        'debt_id',
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

    public function debt()
    {
        return $this->belongsTo(Debt::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
