<?php

namespace App\Policies;

use App\Enums\AccessPermission;
use App\Models\ProductionRunInput;
use App\Models\User;

/**
 * Input snapshots are read-only history.
 */
class ProductionRunInputPolicy
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
    public function view(User $user, ProductionRunInput $productionRunInput): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Snapshots are immutable.
     */
    public function update(User $user, ProductionRunInput $productionRunInput): bool
    {
        return false;
    }

    /**
     * Snapshots are immutable.
     */
    public function delete(User $user, ProductionRunInput $productionRunInput): bool
    {
        return false;
    }
}
