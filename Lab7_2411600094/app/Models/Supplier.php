<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes, Auditable;

    public const OPEN_STATUSES = ['draft', 'pending', 'approved', 'sent'];

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'lead_time_days',
        'address',
        'notes',
        'is_active',
        'payment_terms',
        'minimum_order_amount',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'lead_time_days' => 'integer',
        'minimum_order_amount' => 'decimal:2',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function suppliedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_supplier')
            ->withPivot(['supplier_price', 'supplier_sku', 'is_preferred'])
            ->withTimestamps();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function stickyNotes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable')->latest();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWithLeadTime(Builder $query, int $days): Builder
    {
        return $query->where('lead_time_days', '<=', $days);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%' . trim($term) . '%';

        return $query->where(fn (Builder $builder) => $builder
            ->where('name', 'like', $like)
            ->orWhere('contact_person', 'like', $like)
            ->orWhere('email', 'like', $like));
    }

    public function openOrdersCount(): int
    {
        return $this->purchaseOrders()->whereIn('status', self::OPEN_STATUSES)->count();
    }

    public function getFullContactAttribute(): string
    {
        return implode(' · ', array_filter([$this->contact_person, $this->email, $this->phone]));
    }

    public function getPerformanceRatingAttribute(): ?float
    {
        $delivered = $this->purchaseOrders()
            ->where('status', 'received')
            ->whereNotNull('received_at')
            ->whereNotNull('expected_delivery_date')
            ->get(['received_at', 'expected_delivery_date']);

        if ($delivered->isEmpty()) {
            return null;
        }

        $onTime = $delivered->filter(
            fn (PurchaseOrder $order) => $order->received_at->toDateString() <= $order->expected_delivery_date->toDateString()
        )->count();

        return round($onTime / $delivered->count() * 100, 1);
    }
}
