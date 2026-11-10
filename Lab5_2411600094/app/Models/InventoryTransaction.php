<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    public const TYPE_IN  = 'in';
    public const TYPE_OUT = 'out';

    public const TYPES = [self::TYPE_IN, self::TYPE_OUT];

    protected $fillable = [
        'product_id', 'type', 'quantity', 'reference', 'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /* ---------- Relationships ---------- */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ---------- Helpers ---------- */
    public function isStockIn(): bool
    {
        return $this->type === self::TYPE_IN;
    }

    public function signedQuantity(): int
    {
        return $this->isStockIn() ? $this->quantity : -$this->quantity;
    }

    public function typeLabel(): string
    {
        return $this->isStockIn() ? 'Stock In' : 'Stock Out';
    }

    public function typeBadgeClass(): string
    {
        return $this->isStockIn() ? 'bg-success' : 'bg-danger';
    }

    /* ---------- Business logic ----------
     * Recording a movement immediately re-values the quantity on hand,
     * so the products table never drifts away from the ledger.
     */
    protected static function booted(): void
    {
        static::created(function (InventoryTransaction $transaction) {
            $transaction->product()->increment(
                'quantity',
                $transaction->signedQuantity()
            );
        });

        static::deleting(function (InventoryTransaction $transaction) {
            $transaction->product()->decrement(
                'quantity',
                $transaction->signedQuantity()
            );
        });
    }
}
