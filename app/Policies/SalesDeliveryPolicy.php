<?php

namespace App\Policies;

use App\Models\User;

class SalesDeliveryPolicy extends BasePolicy
{
    protected string $feature = 'penjualan.delivery';
}
