<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /* ---------- Business logic ---------- */
    public function isOutOfStock(): bool
    {
        return $this->quantity === 0;
    }

    /** At or below the reorder threshold, but not empty. */
    public function isLowStock(): bool
    {
        return $this->quantity > 0 && $this->quantity <= $this->reorder_level;
    }

    public function needsReorder(): bool
    {
        return $this->quantity <= $this->reorder_level;
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

    /** quantity x unit price — used for the inventory value stat. */
    public function stockValue(): float
    {
        return round($this->quantity * (float) $this->unit_price, 2);
    }

    public function unitQuantityMissing(): int
    {
        return max(0, $this->reorder_level * 2 - $this->quantity);
    }

    /* ---------- Query scopes ---------- */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0)
                     ->whereColumn('quantity', '<=', 'reorder_level');
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('quantity', 0);
    }
}
