<?php

namespace App\Policies;

use App\Models\User;

class WarehouseTransferPolicy extends BasePolicy
{
    protected string $feature = 'inventory';
}
