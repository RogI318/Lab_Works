<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lab 7 Part 2 Step 3: one product line on a purchase order.
 *
 * `unit_price` is the agreed price when the order was raised, deliberately not
 * a live read of products.unit_price (see the migration note). Receiving
 * advances `received_quantity`, and the gap between the two quantities is what
 * the warehouse still expects.
 */
class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'product_id', 'quantity', 'unit_price', 'received_quantity',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'unit_price'        => 'decimal:2',
        'received_quantity' => 'integer',
    ];

    /* ---------- Relationships ---------- */

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** withTrashed(): a retired product must still appear on old orders. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /* ---------- Accessors ---------- */

    /** quantity x the frozen price, rounded the way the invoice adds up. */
    public function getLineTotalAttribute(): float
    {
        return round($this->quantity * (float) $this->unit_price, 2);
    }

    /** Units the supplier still owes on this line. */
    public function getOutstandingQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->received_quantity);
    }

    public function isFullyReceived(): bool
    {
        return $this->outstanding_quantity === 0;
    }
}
