<?php

namespace App\Policies;

use App\AccessPermission;
use App\Models\Innovation;
use App\Models\User;

class InnovationPolicy
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
    public function view(User $user, Innovation $innovation): bool
    {
        return $user->isActive() && $user->can(AccessPermission::InnovationView->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->can(AccessPermission::InnovationCreate->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Innovation $innovation): bool
    {
        return $user->isActive() && (
            $user->can(AccessPermission::InnovationUpdateAny->value)
            || ($user->can(AccessPermission::InnovationUpdateOwn->value) && $innovation->created_by === $user->id)
        );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Innovation $innovation): bool
    {
        return $this->update($user, $innovation);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Innovation $innovation): bool
    {
        return $user->isActive() && $user->can(AccessPermission::InnovationRestore->value);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Innovation $innovation): bool
    {
        return false;
    }
}
