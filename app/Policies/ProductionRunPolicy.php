<?php

namespace App\Policies;

use App\Enums\AccessPermission;
use App\Models\ProductionRun;
use App\Models\User;

/**
 * Production runs are readable history; completed runs are never edited or deleted.
 */
class ProductionRunPolicy
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
    public function view(User $user, ProductionRun $productionRun): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can complete production runs.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->can(AccessPermission::ProductionManage->value);
    }

    /**
     * Completed runs are immutable.
     */
    public function update(User $user, ProductionRun $productionRun): bool
    {
        return false;
    }

    /**
     * Completed runs are immutable.
     */
    public function delete(User $user, ProductionRun $productionRun): bool
    {
        return false;
    }
}
