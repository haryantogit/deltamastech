<?php

namespace App\Policies;

use App\Models\User;

class PurchaseQuotePolicy extends BasePolicy
{
    protected string $feature = 'pembelian.quote';
}
