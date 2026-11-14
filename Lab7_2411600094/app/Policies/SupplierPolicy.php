<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

/**
 * Lab 7 Part 1 Step 5: per-record rules for suppliers.
 *
 * The role ladder is admin > manager > warehouse > staff. Reading procurement
 * data is open to the three operational roles; only admin and manager may
 * change the master file; only an admin may retire or restore a supplier,
 * because those actions affect purchase orders that other people own.
 *
 * Auto-discovered by Laravel (App\Models\Supplier -> App\Policies\SupplierPolicy);
 * the simple "does this role exist here" checks live in the Gates registered in
 * AppServiceProvider instead, which is why Part 1 demonstrates both.
 */
class SupplierPolicy
{
    /** admin, manager, warehouse */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_WAREHOUSE);
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $this->viewAny($user);
    }

    /** admin, manager */
    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_MANAGER);
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $this->create($user);
    }

    /** admin only */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /** admin only — mirrors products.restore from Lab 6. */
    public function restore(User $user, Supplier $supplier): bool
    {
        return $this->delete($user, $supplier);
    }
}
