<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    // LAB 6: deleting a product moves it to the Trash (it can be restored)
    use SoftDeletes, Auditable;

    // Inactive products are hidden from every query (lists, dashboard, charts, show/edit pages)
    protected static function booted(): void
    {
        static::addGlobalScope('active', function ($query) {
            $query->where('products.is_active', true);
        });
    }

    protected $fillable = [
        'name',
        'sku',
        'brand',
        'description',
        'category',
        'quantity',
        'reorder_level',
        'unit_price',
        'supplier',
        'is_active',
    ];

    protected $casts = [
        'quantity'      => 'integer',
        'reorder_level' => 'integer',
        'unit_price'    => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    /**
     * A product has many stock in/out transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * True when there is no stock left.
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity === 0;
    }

    /**
     * True when stock is above 0 but at or below the reorder level.
     */
    public function isLowStock(): bool
    {
        return $this->quantity > 0 && $this->quantity <= $this->reorder_level;
    }

    /**
     * Returns: Out of Stock, Low Stock, or In Stock.
     */
    public function status(): string
    {
        if ($this->isOutOfStock()) {
            return 'Out of Stock';
        }

        if ($this->isLowStock()) {
            return 'Low Stock';
        }

        return 'In Stock';
    }

    /**
     * Total value of the stock on hand (quantity x unit price).
     */
    public function stockValue(): float
    {
        return round($this->quantity * (float) $this->unit_price, 2);
    }

    // ---------------------------------------------------------------
    // LAB 6 additions (Lab 5 methods above are unchanged)
    // ---------------------------------------------------------------

    // The latest stock movement
    public function latestTransaction()
    {
        return $this->hasOne(InventoryTransaction::class)->latestOfMany();
    }

    // Used by the API and the alert emails
    public function getStockStatusAttribute(): string
    {
        return $this->status();
    }

    // Bootstrap color for the status badge
    public function getStockStatusColorAttribute(): string
    {
        return match ($this->status()) {
            'Out of Stock' => 'danger',
            'Low Stock' => 'warning',
            default => 'success',
        };
    }

    // At or below the reorder level (includes zero) - used for alert lists
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    public function scopeInCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
