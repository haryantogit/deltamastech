<?php

namespace App\Policies;

use App\Models\User;

abstract class BasePolicy
{
    protected string $feature;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, "view_any_{$this->feature}");
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, $model): bool
    {
        return $this->hasPermission($user, "view_any_{$this->feature}"); // Or specific view permission
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, "create_{$this->feature}");
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, $model): bool
    {
        return $this->hasPermission($user, "update_{$this->feature}");
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, $model): bool
    {
        return $this->hasPermission($user, "delete_{$this->feature}");
    }

    protected function hasPermission(User $user, string $permission): bool
    {
        if ($user->role?->name === 'Super Admin') {
            return true;
        }

        return $user->role?->permissions->contains('name', $permission) ?? false;
    }
}
