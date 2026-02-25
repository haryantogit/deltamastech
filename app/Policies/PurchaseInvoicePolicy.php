<?php

namespace App\Policies;

use App\Models\User;

class PurchaseInvoicePolicy extends BasePolicy
{
    protected string $feature = 'pembelian.invoice';
}
