<?php

namespace App\Policies;

use App\Enums\AccessPermission;
use App\Models\StockLedgerEntry;
use App\Models\User;

/**
 * The ledger is read-only for everyone; posting and reversing are separate abilities.
 */
class StockLedgerEntryPolicy
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
    public function view(User $user, StockLedgerEntry $stockLedgerEntry): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can post stock transactions.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->can(AccessPermission::StockPost->value);
    }

    /**
     * Determine whether the user can reverse the entry.
     */
    public function reverse(User $user, StockLedgerEntry $stockLedgerEntry): bool
    {
        return $this->create($user) && $stockLedgerEntry->isReversible();
    }

    /**
     * Ledger entries are never edited.
     */
    public function update(User $user, StockLedgerEntry $stockLedgerEntry): bool
    {
        return false;
    }

    /**
     * Ledger entries are never deleted.
     */
    public function delete(User $user, StockLedgerEntry $stockLedgerEntry): bool
    {
        return false;
    }
}
