<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'confirmed', 'delivered', 'cancelled'];

    protected $fillable = [
        'reference_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'order_date',
        'total',
        'notes',
        'created_by',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'total' => 'decimal:2',
            'delivered_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateReferenceNumber(): string
    {
        $prefix = 'SO-' . now()->format('Ymd') . '-';
        $number = static::withTrashed()->where('reference_number', 'like', $prefix . '%')->count();

        do {
            $number++;
            $reference = $prefix . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('reference_number', $reference)->exists());

        return $reference;
    }

    public function calculateTotal(): void
    {
        $this->total = $this->items()->get()->sum(fn (SalesOrderItem $item) => $item->line_total);
        $this->save();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'confirmed' => 'primary',
            'delivered' => 'success',
            'cancelled' => 'dark',
            default => 'secondary',
        };
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeConfirmed(): bool
    {
        return $this->isDraft();
    }

    public function canBeDelivered(): bool
    {
        return $this->status === 'confirmed';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'confirmed'], true);
    }
}
