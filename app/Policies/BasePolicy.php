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
        return $this->hasPermission($user, "{$this->feature}.view");
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, $model): bool
    {
        return $this->hasPermission($user, "{$this->feature}.view");
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, "{$this->feature}.add");
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, $model): bool
    {
        return $this->hasPermission($user, "{$this->feature}.edit");
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, $model): bool
    {
        return $this->hasPermission($user, "{$this->feature}.delete");
    }

    protected function hasPermission(User $user, string $permission): bool
    {
        if ($user->role?->name === 'Super Admin') {
            return true;
        }

        return $user->role?->permissions->contains('name', $permission) ?? false;
    }
}
