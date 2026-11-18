<?php
// LAB 6: Observer for automatic stock updates
// app/Observers/InventoryTransactionObserver.php
//
// The observer is the ONLY place that changes product quantity when a
// transaction is created. It is registered with #[ObservedBy] on the model.

namespace App\Observers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Notifications\StaffStockOutNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class InventoryTransactionObserver
{
    // STEP 1: BEFORE saving - block any movement that would make stock negative,
    // and remember the stock before and after this movement
    public function creating(InventoryTransaction $transaction): void
    {
        // lockForUpdate: two people cannot change this product at the same moment
        $product = Product::withoutGlobalScopes()->lockForUpdate()->findOrFail($transaction->product_id);

        $after = $product->quantity + $transaction->signed_quantity;

        if ($after < 0) {
            throw ValidationException::withMessages([
                'quantity' => "Not enough stock. {$product->name} only has {$product->quantity} left.",
            ]);
        }

        $transaction->quantity_before = $product->quantity;
        $transaction->quantity_after = $after;
    }

    // STEP 2: AFTER saving (still inside the controller's DB::transaction)
    public function created(InventoryTransaction $transaction): void
    {
        $product = Product::withoutGlobalScopes()->lockForUpdate()->findOrFail($transaction->product_id);

        // The one line that updates stock
        $before = $transaction->quantity_before;
        $product->quantity = $transaction->quantity_after;
        $product->save();

        // STEP 3: Low stock alert, only when stock crosses DOWN to the reorder level
        // (no repeat alerts on every sale after that). Sent to admin AND staff.
        if ($before > $product->reorder_level && $product->quantity <= $product->reorder_level) {
            DB::afterCommit(function () use ($product) {
                $recipients = User::all()->filter(fn (User $user) => $user->canManageStock());

                Notification::send($recipients, new LowStockNotification($product));
            });
        }

        // STEP 4: a STAFF member stocked out, so tell the admins (bell only, no email)
        $transaction->loadMissing(['product', 'user']);

        if ($transaction->type === 'out' && $transaction->user?->isStaff()) {
            DB::afterCommit(function () use ($transaction) {
                $admins = User::where('role', 'admin')->get();

                Notification::send($admins, new StaffStockOutNotification($transaction));
            });
        }
    }
}
