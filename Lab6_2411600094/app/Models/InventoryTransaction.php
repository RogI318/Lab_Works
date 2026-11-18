<?php

namespace App\Models;

use App\Observers\InventoryTransactionObserver;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(InventoryTransactionObserver::class)]
class InventoryTransaction extends Model
{
    use Auditable;
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';

    /** quantity is the signed difference (physical - system) */
    public const TYPE_ADJUSTMENT = 'adjustment';

    /** From Lab 6 (2411600101): a customer return adds stock back, damaged goods take it out. */
    public const TYPE_RETURN = 'return';
    public const TYPE_DAMAGE = 'damage';

    public const TYPES = [
        self::TYPE_IN,
        self::TYPE_OUT,
        self::TYPE_ADJUSTMENT,
        self::TYPE_RETURN,
        self::TYPE_DAMAGE,
    ];

    public function auditLabel(): string
    {
        $this->loadMissing(['user', 'product']);
        return sprintf(
            '%s recorded %s of %s unit(s) for %s; stock %s → %s%s',
            $this->user?->name ?? 'Unknown user',
            $this->typeLabel(),
            abs($this->signedQuantity()),
            $this->product?->name ?? 'Deleted product',
            $this->quantity_before ?? '?',
            $this->quantity_after ?? '?',
            $this->reference_number ? '; reference ' . $this->reference_number : ''
        );
    }

    /** the causes the adjustment form offers. */
    public const REASONS = [
        'count_discrepancy' => 'Physical count discrepancy',
        'damaged' => 'Damaged goods',
        'theft_loss' => 'Theft / loss',
        'system_error' => 'System error',
        'other' => 'Other (explain in notes)',
    ];

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'reference_number',
        'notes',
        'reason',
        'user_id',
        'quantity_before',
        'quantity_after',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
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

    /*  Relationships  */
    /**
     * Ledger rows keep resolving their product even after it is soft-deleted:
     * a movement is a historical fact and its report must still name it.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
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

    /** True for the two movement types that take stock out (Stock Out, Damage). */
    public function isOutgoing(): bool
    {
        return in_array($this->type, [self::TYPE_OUT, self::TYPE_DAMAGE], true);
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
            self::TYPE_IN, self::TYPE_RETURN => abs($this->quantity),
            self::TYPE_OUT, self::TYPE_DAMAGE => -abs($this->quantity),
            default => (int) $this->quantity,
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_IN => 'Stock In',
            self::TYPE_OUT => 'Stock Out',
            self::TYPE_RETURN => 'Return',
            self::TYPE_DAMAGE => 'Damage',
            default => 'Adjustment',
        };
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            self::TYPE_IN, self::TYPE_RETURN => 'bg-success',
            self::TYPE_OUT, self::TYPE_DAMAGE => 'bg-danger',
            default => 'bg-info text-dark',
        };
    }

    /** Part 8: human-readable reason for an adjustment row. */
    public function reasonLabel(): ?string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
