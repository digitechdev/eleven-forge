<?php

namespace App\Policies;

use App\Enums\AccessPermission;
use App\Enums\BuildOrderStatus;
use App\Models\BuildOrder;
use App\Models\User;

/**
 * Anyone who can browse innovations can see build orders; only production managers change them.
 */
class BuildOrderPolicy
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
    public function view(User $user, BuildOrder $buildOrder): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->can(AccessPermission::ProductionManage->value);
    }

    /**
     * Determine whether the user can move a draft order to planned.
     */
    public function plan(User $user, BuildOrder $buildOrder): bool
    {
        return $this->create($user) && $buildOrder->status->canTransitionTo(BuildOrderStatus::Planned);
    }

    /**
     * Determine whether the user can start a planned order.
     */
    public function start(User $user, BuildOrder $buildOrder): bool
    {
        return $this->create($user) && $buildOrder->status->canTransitionTo(BuildOrderStatus::InProgress);
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(User $user, BuildOrder $buildOrder): bool
    {
        return $this->create($user) && $buildOrder->status->canTransitionTo(BuildOrderStatus::Cancelled);
    }

    /**
     * Determine whether the user can complete a production run against the order.
     */
    public function complete(User $user, BuildOrder $buildOrder): bool
    {
        return $this->create($user) && $buildOrder->status->acceptsProduction();
    }

    /**
     * Orders change only through their status actions.
     */
    public function update(User $user, BuildOrder $buildOrder): bool
    {
        return false;
    }

    /**
     * Orders are kept for history; cancel them instead.
     */
    public function delete(User $user, BuildOrder $buildOrder): bool
    {
        return false;
    }
}
