<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Contact;
use App\Models\DebtItem;
use App\Models\Tag;

class Debt extends Model
{
    use \App\Traits\HasAutomaticNumbering;

    public function getNumberingSettingKey(): ?string
    {
        return 'hutang';
    }
    protected $fillable = [
        'number',
        'supplier_id',
        'date',
        'due_date',
        'reference',
        'notes',
        'total_amount',
        'status',
        'payment_status',
        'attachments'
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function supplier()
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(DebtItem::class);
    }

    public function payments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
