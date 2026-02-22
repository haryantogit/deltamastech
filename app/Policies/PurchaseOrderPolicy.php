<?php

namespace App\Policies;

use App\Models\User;

class PurchaseOrderPolicy extends BasePolicy
{
    protected string $feature = 'purchase';
}
