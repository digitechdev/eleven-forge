<?php

namespace App\Policies;

use App\AccessPermission;
use App\Models\Technology;
use App\Models\User;

class TechnologyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->can(AccessPermission::CatalogView->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Technology $technology): bool
    {
        return $user->isActive() && $user->can(AccessPermission::CatalogView->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canManageCatalog();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Technology $technology): bool
    {
        return $user->canManageCatalog();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Technology $technology): bool
    {
        return $user->canManageCatalog();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Technology $technology): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Technology $technology): bool
    {
        return false;
    }
}
