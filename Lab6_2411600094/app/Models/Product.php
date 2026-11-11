<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Product extends Model
{
    public const STATUS_IN_STOCK  = 'In Stock';
    public const STATUS_LOW_STOCK = 'Low Stock';
    public const STATUS_OUT_STOCK = 'Out of Stock';

    protected $fillable = [
        'name', 'sku', 'description', 'category_id', 'brand_id',
        'quantity', 'reorder_level', 'unit_price', 'supplier',
    ];

    protected $casts = [
        'quantity'      => 'integer',
        'reorder_level' => 'integer',
        'unit_price'    => 'decimal:2',
    ];

    /* ---------- Relationships ---------- */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /** Part 4 Step 1: the single newest ledger row for this product. */
    public function latestTransaction(): HasOne
    {
        return $this->hasOne(InventoryTransaction::class)->latestOfMany('created_at');
    }

    /* ---------- Business logic ---------- */
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }

    /** Part 4 Step 2 (docx definition): quantity <= reorder level. */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->reorder_level;
    }

    public function needsReorder(): bool
    {
        return $this->isLowStock();
    }

    public function stockStatus(): string
    {
        if ($this->isOutOfStock()) return self::STATUS_OUT_STOCK;
        if ($this->isLowStock())   return self::STATUS_LOW_STOCK;

        return self::STATUS_IN_STOCK;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->stockStatus()) {
            self::STATUS_LOW_STOCK => 'bg-warning text-dark',
            self::STATUS_OUT_STOCK => 'bg-danger',
            default                => 'bg-success',
        };
    }

    /* ---------- Part 4 Step 2: docx-style accessors ---------- */
    public function getStockStatusAttribute(): string
    {
        return $this->stockStatus();
    }

    /** Plain Bootstrap color name: alert-{color}, text-{color}, … */
    public function getStockStatusColorAttribute(): string
    {
        return match ($this->stockStatus()) {
            self::STATUS_LOW_STOCK => 'warning',
            self::STATUS_OUT_STOCK => 'danger',
            default                => 'success',
        };
    }

    /** quantity x unit price — used for the inventory value stat. */
    public function stockValue(): float
    {
        return round($this->quantity * (float) $this->unit_price, 2);
    }

    public function unitQuantityMissing(): int
    {
        return max(0, $this->reorder_level * 2 - $this->quantity);
    }

    /**
     * Part 9 Step 1: this product's ledger, oldest first, with the quantity
     * that stood after each movement in `balance_after`.
     *
     * Balances are walked forward from (current quantity − all recorded
     * movements), so a date filter only hides rows — it can never make the
     * arithmetic wrong, and adjustments need no special case.
     */
    public function transactionHistory(?string $from = null, ?string $to = null): Collection
    {
        $all = $this->transactions()
            ->with('user')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $balance = $this->quantity - (int) $all->sum(fn (InventoryTransaction $t) => $t->signedQuantity());

        $all->each(function (InventoryTransaction $t) use (&$balance) {
            $balance += $t->signedQuantity();
            $t->setAttribute('balance_after', $balance);
        });

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate   = $to ? Carbon::parse($to)->endOfDay() : null;

        return $all->filter(function (InventoryTransaction $t) use ($fromDate, $toDate) {
            return (! $fromDate || $t->created_at->gte($fromDate))
                && (! $toDate || $t->created_at->lte($toDate));
        })->values();
    }

    /* ---------- Query scopes ----------
     * scopeLowStock / scopeOutOfStock stay mutually exclusive (low = not
     * empty but at/below threshold) because the dashboard's In/Low/Out
     * stat cards must add up to the product total. The docx's full
     * "at or below reorder level" set is isLowStock() / needsReorder().
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0)
                     ->whereColumn('quantity', '<=', 'reorder_level');
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('quantity', '<=', 0);
    }

    /**
     * The docx wording for alerts and the Low Stock report: every product at
     * or below its reorder level, empties included.
     */
    public function scopeBelowReorderLevel(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level');
    }

    /** Part 4 Step 3: accepts a Category model, its id, or its name. */
    public function scopeInCategory(Builder $query, Category|int|string $category): Builder
    {
        if (is_string($category) && ! ctype_digit($category)) {
            return $query->whereHas('category', fn (Builder $q) => $q->where('name', $category));
        }

        $id = $category instanceof Category ? $category->id : (int) $category;

        return $query->where('category_id', $id);
    }
}
