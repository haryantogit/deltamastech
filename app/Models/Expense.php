<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Expense extends Model
{
    use HasFactory, \App\Traits\HasAutomaticNumbering;

    public function getNumberingSettingKey(): ?string
    {
        return 'expense';
    }

    protected function getNumberingField(): string
    {
        return 'reference_number';
    }

    protected $fillable = [
        'account_id',
        'contact_id',
        'transaction_date',
        'reference_number',
        'reference',
        'memo',
        'is_pay_later',
        'sub_total',
        'tax_total',
        'total_amount',
        'remaining_amount',
        'attachments',
        'term_id',
        'due_date',
        'is_recurring',
        'tax_inclusive',
        'discount_amount',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'is_pay_later' => 'boolean',
        'attachments' => 'json',
        'due_date' => 'date',
        'is_recurring' => 'boolean',
        'tax_inclusive' => 'boolean',
        'discount_amount' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
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
