<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff() || $user->isManager();
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === 'draft'
            && ($user->isAdmin() || (int) $purchaseOrder->created_by === (int) $user->id);
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === 'draft' && $user->isAdmin();
    }

    public function submit(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === 'draft'
            && ($user->isAdmin() || $user->isManager() || (int) $purchaseOrder->created_by === (int) $user->id);
    }

    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function send(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->canBeCancelled()
            && ($user->isAdmin() || $user->isManager() || (int) $purchaseOrder->created_by === (int) $user->id);
    }
}
