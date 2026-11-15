<?php

namespace App\Models;

use App\Support\SqlLike;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Product extends Model
{
    use SoftDeletes;

    public const STATUS_IN_STOCK  = 'In Stock';
    public const STATUS_LOW_STOCK = 'Low Stock';
    public const STATUS_OUT_STOCK = 'Out of Stock';

    /** The three labels a stock-status dropdown offers, in that order. */
    public const STOCK_STATUSES = [self::STATUS_IN_STOCK, self::STATUS_LOW_STOCK, self::STATUS_OUT_STOCK];

    protected $fillable = [
        'name', 'sku', 'description', 'category_id', 'brand_id', 'supplier_id',
        'quantity', 'reorder_level', 'unit_price', 'supplier',
    ];

    protected $casts = [
        'quantity'      => 'integer',
        'reorder_level' => 'integer',
        'unit_price'    => 'decimal:2',
        'supplier_id'   => 'integer',
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

    /**
     * Lab 7 Part 1 Step 4: the PRIMARY supplier (products.supplier_id).
     *
     * Named primarySupplier() rather than supplier() on purpose: `supplier` is
     * still a real column on this table (Lab 5's free-text field, kept because
     * Reports group by it), and an attribute always beats a relation of the
     * same name — $product->supplier would quietly keep returning the old text.
     * Use $product->primarySupplier for the model and $product->supplier_name
     * for the label.
     */
    public function primarySupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /** Lab 7 Part 1: every supplier holding a price agreement for this item. */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier', 'product_id', 'supplier_id')
                    ->withPivot(Supplier::PIVOT_COLUMNS)
                    ->withTimestamps();
    }

    /** Preferred first, so $product->suppliers->first() is the best price. */
    public function preferredSupplier(): BelongsToMany
    {
        return $this->suppliers()->wherePivot('is_preferred', true)->limit(1);
    }

    /** Relation name when linked, otherwise the legacy free-text supplier. */
    public function getSupplierNameAttribute(): string
    {
        return $this->primarySupplier?->name
            ?? $this->getAttributeValue('supplier')
            ?? '';
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Lab 7 Part 2: every order line this product appears on. Soft-deleted
     * orders count — their lines still hold the foreign key, which is exactly
     * why a purchased product cannot be erased.
     */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
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

    /* ---------- Query scopes (Lab 7 Part 7 Step 2) ---------- */

    /**
     * Free-text search across the three columns the inventory box has always
     * matched on: the name, the SKU, and the legacy supplier text. That last
     * one stays in the list because Lab 6's Reports still GROUP BY it, so a
     * search that ignored it would miss rows the reports still name.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return SqlLike::any($query, ['name', 'sku', 'supplier'], $term);
    }

    /** The real supplier link (Lab 7 Part 1), as a Supplier model or an id. */
    public function scopeForSupplier(Builder $query, Supplier|int|null $supplier): Builder
    {
        $id = $supplier instanceof Supplier ? $supplier->id : $supplier;

        return $query->when($id, fn (Builder $q) => $q->where('supplier_id', (int) $id));
    }

    /** Mirrors scopeInCategory: a Brand model, its id, or its name. */
    public function scopeInBrand(Builder $query, Brand|int|string $brand): Builder
    {
        if (is_string($brand) && ! ctype_digit($brand)) {
            return $query->whereHas('brand', fn (Builder $q) => $q->where('name', $brand));
        }

        $id = $brand instanceof Brand ? $brand->id : (int) $brand;

        return $query->where('brand_id', $id);
    }

    /** The third of the In/Low/Out triple; the other two already existed. */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '>', 'reorder_level');
    }

    /** Everything the stock-status filter can ask for, by its display label. */
    public function scopeStockStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            self::STATUS_LOW_STOCK => $query->lowStock(),
            self::STATUS_OUT_STOCK => $query->outOfStock(),
            self::STATUS_IN_STOCK  => $query->inStock(),
            default                => $query,
        };
    }
}
