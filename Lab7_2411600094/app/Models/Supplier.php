<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Lab 7 Part 1 Step 4: the supplier master record.
 *
 * A supplier reaches a product two ways: products.supplier_id (the PRIMARY
 * vendor the reorder engine buys from) and the product_supplier pivot (every
 * vendor that has a price agreement for the item, with its own cost, SKU,
 * order multiple and preferred flag).
 */
class Supplier extends Model
{
    use SoftDeletes, LogsActivity;

    /** The only payment terms the forms accept. */
    public const PAYMENT_TERMS = ['COD', 'Net 15', 'Net 30', 'Net 45', 'Net 60'];

    protected $fillable = [
        'name', 'contact_person', 'email', 'phone', 'address',
        'payment_terms', 'lead_time_days', 'minimum_order_amount',
        'is_active', 'notes',
    ];

    protected $casts = [
        'lead_time_days'       => 'integer',
        'minimum_order_amount' => 'decimal:2',
        'is_active'            => 'boolean',
    ];

    /* ---------- Relationships ---------- */

    /** Products whose primary supplier this is. */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** Every purchase order raised against this supplier (model arrives in Part 2). */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /** Products with a price agreement here, pivot terms included. */
    public function pricedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_supplier', 'supplier_id', 'product_id')
                    ->withPivot(Supplier::PIVOT_COLUMNS)
                    ->withTimestamps();
    }

    /** The columns the pivot carries; one list so every query agrees. */
    public const PIVOT_COLUMNS = [
        'supplier_sku', 'unit_cost', 'minimum_order_quantity', 'package_size', 'is_preferred',
    ];

    /** Preferred = the vendor on the product's PO line unless overridden. */
    public function preferredProducts(): BelongsToMany
    {
        return $this->pricedProducts()->wherePivot('is_preferred', true);
    }

    /* ---------- Query scopes (Step 4) ---------- */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /** Suppliers that can deliver within $days (used by the Part 3 engine). */
    public function scopeWithLeadTime(Builder $query, int $days): Builder
    {
        return $query->where('lead_time_days', '<=', $days);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('contact_person', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }

    /* ---------- Presentation helpers ---------- */

    /** "Metro Lumber Supply — Rosa Dela Cruz — sales@metrolumber.ph" */
    public function getFullContactAttribute(): string
    {
        return implode(' — ', array_filter([
            $this->contact_person,
            $this->email,
            $this->phone,
        ]));
    }

    /**
     * On-time delivery percentage over received purchase orders, or null when
     * nothing has been received yet (never show a fake 100 %).
     *
     * TODO Part 6: this simple ratio becomes the full performance metric —
     * fill rate, lead-time variance and quality rejects — cached per supplier.
     */
    public function getPerformanceRatingAttribute(): ?float
    {
        $received = PurchaseOrder::query()
            ->where('supplier_id', $this->id)
            ->where('status', PurchaseOrder::STATUS_RECEIVED)
            ->whereNotNull('expected_delivery_date')
            ->get(['expected_delivery_date', 'received_at']);

        if ($received->isEmpty()) {
            return null;
        }

        $onTime = $received->filter(
            fn (PurchaseOrder $po) => $po->received_at !== null
                && $po->received_at->lte($po->expected_delivery_date)
        )->count();

        return round($onTime / $received->count() * 100, 1);
    }

    public function performanceBadgeClass(): string
    {
        return match (true) {
            $this->performance_rating === null => 'bg-secondary',
            $this->performance_rating >= 90    => 'bg-success',
            $this->performance_rating >= 75    => 'bg-warning text-dark',
            default                            => 'bg-danger',
        };
    }

    public function statusBadgeClass(): string
    {
        return $this->is_active ? 'bg-success' : 'bg-secondary';
    }

    /* ---------- Business logic ---------- */

    /**
     * Statuses that still expect something from the supplier, so a supplier in
     * the middle of one of them must not be retired. Lab 7 Part 2: the
     * PurchaseOrder model now exists, so this is a real query.
     */
    public function activeOrderStatuses(): array
    {
        return PurchaseOrder::ACTIVE_STATUSES;
    }

    public function hasActivePurchaseOrders(): bool
    {
        return $this->purchaseOrders()
                    ->whereIn('status', $this->activeOrderStatuses())
                    ->exists();
    }

    /** Lead time in days, spelled the way the tables show it. */
    public function leadTimeLabel(): string
    {
        return $this->lead_time_days === 1 ? '1 day' : "{$this->lead_time_days} days";
    }
}
