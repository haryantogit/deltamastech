<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'photo', // Added
        'nik',   // Added
        'name',
        'type',
        'salutation', // Added
        'company',
        'tax_id',
        'email',
        'phone',
        'mobile', // Added
        'address',
        'city',
        'province',    // Added
        'postal_code', // Added
        'country',     // Added
        'receivable_account_id', // Added
        'payable_account_id',    // Added
        'credit_limit',
        'receivable_limit',
        'bank_name',           // Added
        'bank_account_no',     // Added
        'bank_account_holder', // Added
    ];

    public function receivableAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class, 'receivable_account_id');
    }

    public function payableAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class, 'payable_account_id');
    }

    public function bankAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ContactBankAccount::class);
    }

    public function salesInvoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function purchaseInvoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseInvoice::class, 'supplier_id');
    }

    public function salesOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }

    public function purchaseOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }

    public function salesQuotations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SalesQuotation::class);
    }

    public function salesDeliveries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SalesDelivery::class, 'customer_id');
    }

    public function purchaseDeliveries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseDelivery::class, 'supplier_id');
    }

    public function expenses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function receivables(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Receivable::class);
    }

    public function debts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Debt::class, 'supplier_id');
    }
}
