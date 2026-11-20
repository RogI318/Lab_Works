<?php

namespace App\Observers;

use App\Models\Product;

/**
 * A product alerts as soon as it is saved while
 * at or below its reorder level — not only after a stock movement.
 *
 * Quantity changes already go through the transaction ledger (and its observer),
 * so the case this covers is editing a product so that it falls under its
 * threshold, e.g. raising the reorder level above the quantity on hand.
 * New products are skipped: they start at 0 and receive their opening stock
 * as a movement a moment later.
 */
class ProductObserver
{
    public function updated(Product $product): void
    {
        if (! $product->wasChanged(['reorder_level', 'quantity'])) {
            return;
        }

        // Same recipients and unread-duplicate rule as the ledger alerts.
        InventoryTransactionObserver::alertAdminsIfLow($product);
    }
}
