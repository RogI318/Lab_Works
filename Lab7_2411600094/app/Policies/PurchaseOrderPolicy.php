<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

/**
 * Lab 7 Part 2 Step 5: who may do what to a purchase order.
 *
 *   action    allowed roles              state gate                    extra rule
 *   -------   -------------------------  ----------------------------  ------------------------------
 *   viewAny   admin, manager, warehouse  -                             -
 *   view      admin, manager, warehouse  -                             -
 *   create    admin, manager, warehouse  -                             -
 *   update    admin, creator             draft only                    -
 *   submit    admin, creator             draft only                    -
 *   approve   admin, manager             pending only                  a manager may NOT approve
 *                                                                       the order they wrote
 *   reject    admin, manager             pending only                  -
 *   send      admin, manager             approved only                 -
 *   cancel    admin, manager             draft, pending, approved      -
 *   receive   admin, warehouse           sent, partially_received      -
 *   delete    admin                      draft only                    -
 *
 * **Why the state lives here too:** the buttons on the show page are wrapped in
 * @can, so a rule that only existed in the controller would still offer an
 * action the server then refuses. The controller re-checks with the canBe*()
 * helpers to answer with a readable flash message instead of a 403.
 *
 * Separation of duties is the point of two rules: whoever raises an order
 * cannot be the one who approves it, and the warehouse that receives goods
 * cannot approve or edit them.
 */
class PurchaseOrderPolicy
{
    /** admin, manager, warehouse — staff never see procurement at all. */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_WAREHOUSE);
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /** Draft only, and only for the person who started it (admins may tidy any). */
    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->canBeEdited()
            && ($user->hasRole(User::ROLE_ADMIN) || $purchaseOrder->created_by === $user->id);
    }

    public function submit(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->update($user, $purchaseOrder);
    }

    /**
     * Approval is a manager's or admin's call on a pending order. A manager who
     * raised the order themselves is not allowed to sign off on it — an admin
     * has to, which is the whole reason the two roles are separate.
     */
    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if (! $purchaseOrder->canBeApproved()) {
            return false;
        }

        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }

        return $user->hasRole(User::ROLE_MANAGER) && $purchaseOrder->created_by !== $user->id;
    }

    /** Rejecting is the same decision authority as approving. */
    public function reject(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->canBeRejected()
            && $user->hasRole(User::ROLE_ADMIN, User::ROLE_MANAGER);
    }

    public function send(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->canBeSent()
            && $user->hasRole(User::ROLE_ADMIN, User::ROLE_MANAGER);
    }

    /** Cancelling stops a commitment, so it stays with manager and admin. */
    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->canBeCancelled()
            && $user->hasRole(User::ROLE_ADMIN, User::ROLE_MANAGER);
    }

    /** Goods in is the warehouse's job (admins can cover a shift). */
    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->canBeReceived()
            && $user->hasRole(User::ROLE_ADMIN, User::ROLE_WAREHOUSE);
    }

    /** Soft delete of a draft: admin only, like the Lab 6 master-file rules. */
    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->canBeEdited() && $user->hasRole(User::ROLE_ADMIN);
    }
}
