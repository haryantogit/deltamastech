<?php

namespace App\Policies;

use App\Models\User;

class PurchaseDeliveryPolicy extends BasePolicy
{
    protected string $feature = 'pembelian.delivery';
}
