<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'contact_id',
        'transaction_date',
        'reference_number',
        'memo',
        'is_pay_later',
        'sub_total',
        'tax_total',
        'total_amount',
        'remaining_amount',
        'attachments',
        'due_date',
        'is_recurring',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'is_pay_later' => 'boolean',
        'attachments' => 'json',
        'due_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
