<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receivable extends Model
{
    protected $fillable = [
        'invoice_number',
        'contact_id',
        'transaction_date',
        'due_date',
        'reference',
        'notes',
        'total_amount',
        'status',
        'attachments'
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function items()
    {
        return $this->hasMany(ReceivableItem::class);
    }

    public function payments()
    {
        return $this->hasMany(ReceivablePayment::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
