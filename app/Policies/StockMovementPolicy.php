<?php

namespace App\Policies;

use App\Models\User;

class StockMovementPolicy extends BasePolicy
{
    protected string $feature = 'inventory';
}
