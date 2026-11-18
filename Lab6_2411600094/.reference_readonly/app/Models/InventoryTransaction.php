<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Observers\InventoryTransactionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// LAB 6: the observer updates the product quantity every time a transaction is created
#[ObservedBy([InventoryTransactionObserver::class])]
class InventoryTransaction extends Model
{
    use Auditable;

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'reference',
        'quantity_before',
        'quantity_after',
        'reason',
        'notes',
        'user_id',
    ];

    // Lab 5 convention (kept): "in" and "out" store a positive quantity.
    // Lab 6: an "adjustment" stores the signed difference (+ or -).
    protected $casts = [
        'quantity' => 'integer',
    ];

    // Relationship: a transaction belongs to one product.
    // (Deactivated products are hidden elsewhere, but their history stays readable.)
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withoutGlobalScopes();
    }

    // Relationship: a transaction was made by one user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Text shown in the audit log
    public function auditLabel(): string
    {
        return $this->type_label.' '.$this->signed_quantity.' x '.($this->product?->name ?? 'product #'.$this->product_id);
    }

    // +in, -out, adjustment = the difference. This is what changes the stock.
    public function getSignedQuantityAttribute(): int
    {
        return match ($this->type) {
            'out' => -abs((int) $this->quantity),
            'in' => abs((int) $this->quantity),
            default => (int) $this->quantity,
        };
    }

    public function scopeStockIn($query)
    {
        return $query->where('type', 'in');
    }

    public function scopeStockOut($query)
    {
        return $query->where('type', 'out');
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    // Label and badge color used by the views
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'in' => 'Stock In',
            'out' => 'Stock Out',
            default => 'Adjustment',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'in' => 'success',
            'out' => 'danger',
            default => 'secondary',
        };
    }
}
