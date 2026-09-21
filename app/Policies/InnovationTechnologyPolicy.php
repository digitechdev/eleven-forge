<?php

namespace App\Policies;

use App\AccessPermission;
use App\Models\Innovation;
use App\Models\InnovationTechnology;
use App\Models\User;

class InnovationTechnologyPolicy
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
    public function view(User $user, InnovationTechnology $innovationTechnology): bool
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
    public function update(User $user, InnovationTechnology $innovationTechnology): bool
    {
        return $user->can('update', $innovationTechnology->innovation);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InnovationTechnology $innovationTechnology): bool
    {
        return $this->update($user, $innovationTechnology);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InnovationTechnology $innovationTechnology): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InnovationTechnology $innovationTechnology): bool
    {
        return false;
    }
}
