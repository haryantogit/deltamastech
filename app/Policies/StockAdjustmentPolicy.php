<?php

namespace App\Policies;

use App\Models\User;

class StockAdjustmentPolicy extends BasePolicy
{
    protected string $feature = 'inventory';
}
