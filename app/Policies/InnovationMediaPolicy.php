<?php

namespace App\Policies;

use App\AccessPermission;
use App\Models\Innovation;
use App\Models\InnovationMedia;
use App\Models\User;

class InnovationMediaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->can(AccessPermission::InnovationView->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InnovationMedia $innovationMedia): bool
    {
        return $user->isActive() && $user->can(AccessPermission::InnovationView->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Innovation $innovation): bool
    {
        return $user->can('update', $innovation);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InnovationMedia $innovationMedia): bool
    {
        return $user->can('update', $innovationMedia->innovation);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InnovationMedia $innovationMedia): bool
    {
        return $this->update($user, $innovationMedia);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InnovationMedia $innovationMedia): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InnovationMedia $innovationMedia): bool
    {
        return false;
    }
}
