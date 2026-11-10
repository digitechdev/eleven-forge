<?php

namespace App\Policies;

use App\Enums\AccessPermission;
use App\Enums\FormulaStatus;
use App\Models\Innovation;
use App\Models\InnovationFormula;
use App\Models\User;

/**
 * Formulas follow their innovation: anyone who can view it sees them, and those who can edit it manage them.
 */
class InnovationFormulaPolicy
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
    public function view(User $user, InnovationFormula $innovationFormula): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can start a new formula version for the innovation.
     */
    public function create(User $user, Innovation $innovation): bool
    {
        return $user->can('update', $innovation);
    }

    /**
     * Determine whether the user can change the formula's lines and yield.
     */
    public function update(User $user, InnovationFormula $innovationFormula): bool
    {
        return $user->can('update', $innovationFormula->innovation) && $innovationFormula->isEditable();
    }

    /**
     * Determine whether the user can make this draft the innovation's active formula.
     */
    public function activate(User $user, InnovationFormula $innovationFormula): bool
    {
        return $user->can('update', $innovationFormula->innovation) && $innovationFormula->status === FormulaStatus::Draft;
    }

    /**
     * Formulas are kept for history; superseded ones are archived, never deleted.
     */
    public function delete(User $user, InnovationFormula $innovationFormula): bool
    {
        return false;
    }
}
