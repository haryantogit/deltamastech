<?php

namespace App\Policies;

use App\Models\User;

class SalesInvoicePolicy extends BasePolicy
{
    protected string $feature = 'sales';
}
