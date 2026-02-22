<?php

namespace App\Policies;

use App\Models\User;

class WarehousePolicy extends BasePolicy
{
    protected string $feature = 'inventory';
}
