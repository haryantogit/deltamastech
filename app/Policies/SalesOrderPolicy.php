<?php

namespace App\Policies;

use App\Models\User;

class SalesOrderPolicy extends BasePolicy
{
    protected string $feature = 'sales';
}
