<?php

namespace App\Observers;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Lab 6 Part 2: the single place where the ledger re-values stock on hand.
 * (Replaces the created/deleting hooks Lab 5 put in the model's booted().)
 */
class InventoryTransactionObserver
{
    /**
     * created vs creating: the stock UPDATE must run in `created` (the row
     * has to exist first), but the negative-inventory guard runs in
     * `creating` — aborting there means MySQL never inserts a doomed row,
     * even if the caller forgot to wrap create() in DB::transaction().
     */
    public function creating(InventoryTransaction $transaction): void
    {
        if ($transaction->signedQuantity() >= 0) {
            return; // stock-in and upward adjustments can never go negative
        }

        $product = Product::whereKey($transaction->product_id)->lockForUpdate()->firstOrFail();

        if ($product->quantity + $transaction->signedQuantity() < 0) {
            throw new InsufficientStockException(sprintf(
                'Not enough stock for "%s": %d on hand, %d required.',
                $product->name,
                $product->quantity,
                abs($transaction->signedQuantity())
            ));
        }
    }

    /**
     * Option A from the docx Critical Thinking: an over-sized stock-out
     * throws and the surrounding DB::transaction rolls the whole operation
     * back, so stock and history never disagree.
     */
    public function created(InventoryTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $product = $transaction->product()->firstOrFail();
            $delta   = $transaction->signedQuantity();

            $before = (int) $product->quantity;
            $after  = $before + $delta;

            if ($after < 0) {
                throw new InsufficientStockException(sprintf(
                    'Not enough stock for "%s": %d on hand, %d required.',
                    $product->name,
                    $before,
                    abs($delta)
                ));
            }

            // Increment through the relation: writes straight to MySQL, so a
            // stale in-memory copy can never clobber a concurrent movement.
            $transaction->product()->increment('quantity', $delta);
            $product->refresh();

            // Part 6 Step 3 — low stock alert when the movement crosses the
            // threshold from above. The crossing (not the state) is the
            // trigger, which is also the duplicate suppression: further
            // drawdowns while ALREADY low do not re-alert, but a refill
            // back above the level arms it again.
            if ($delta < 0 && $before > $product->reorder_level && $after <= $product->reorder_level) {
                Notification::send(User::admins()->get(), new LowStockNotification($product));
            }
        });
    }

    /**
     * Deleting a ledger row reverses its stock impact (admin-only in Part 3).
     * If the product was cascade-deleted with its transactions there is
     * nothing left to correct.
     */
    public function deleted(InventoryTransaction $transaction): void
    {
        $product = $transaction->product()->first();

        if ($product) {
            $transaction->product()->decrement('quantity', $transaction->signedQuantity());
        }
    }
}
