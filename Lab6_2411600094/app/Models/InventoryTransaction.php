<?php

namespace App\Models;

use App\Observers\InventoryTransactionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(InventoryTransactionObserver::class)]
class InventoryTransaction extends Model
{
    public const TYPE_IN  = 'in';
    public const TYPE_OUT = 'out';

    /** Part 8: quantity is the signed difference (physical - system). */
    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPES = [self::TYPE_IN, self::TYPE_OUT, self::TYPE_ADJUSTMENT];

    /** Part 8 Step 2: the causes the adjustment form offers. */
    public const REASONS = [
        'count_discrepancy' => 'Physical count discrepancy',
        'damaged'           => 'Damaged goods',
        'theft_loss'        => 'Theft / loss',
        'system_error'      => 'System error',
        'other'             => 'Other (explain in notes)',
    ];

    protected $fillable = [
        'product_id', 'type', 'quantity', 'reference_number', 'notes', 'reason', 'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /* ---------- Query scopes (Part 1, Step 4) ---------- */
    public function scopeStockIn($query)
    {
        return $query->where('type', self::TYPE_IN);
    }

    public function scopeStockOut($query)
    {
        return $query->where('type', self::TYPE_OUT);
    }

    public function scopeAdjustments($query)
    {
        return $query->where('type', self::TYPE_ADJUSTMENT);
    }

    public function scopeRecent($query)
    {
        return $query->latest('created_at');
    }

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

    public function isAdjustment(): bool
    {
        return $this->type === self::TYPE_ADJUSTMENT;
    }

    /**
     * The signed impact on stock: in/out rows store a positive magnitude and
     * `type` gives the direction; adjustment rows already store the signed
     * difference, so it passes through unchanged.
     */
    public function signedQuantity(): int
    {
        return match ($this->type) {
            self::TYPE_IN  => abs($this->quantity),
            self::TYPE_OUT => -abs($this->quantity),
            default        => (int) $this->quantity,
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_IN         => 'Stock In',
            self::TYPE_OUT        => 'Stock Out',
            default               => 'Adjustment',
        };
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            self::TYPE_IN         => 'bg-success',
            self::TYPE_OUT        => 'bg-danger',
            default               => 'bg-info text-dark',
        };
    }

    /** Part 8: human-readable reason for an adjustment row. */
    public function reasonLabel(): ?string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
