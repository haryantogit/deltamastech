<?php

namespace App\Policies;

use App\Models\User;

class ContactPolicy extends BasePolicy
{
    protected string $feature = 'kontak.list';
}
