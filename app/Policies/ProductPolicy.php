<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy extends BasePolicy
{
    protected string $feature = 'produk.list';

    public function deactivate(User $user): bool
    {
        return $this->hasPermission($user, "{$this->feature}.deactivate");
    }
}
