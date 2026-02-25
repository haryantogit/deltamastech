<?php

namespace App\Policies;

use App\Models\User;

class SalesQuotationPolicy extends BasePolicy
{
    protected string $feature = 'penjualan.quote';
}
