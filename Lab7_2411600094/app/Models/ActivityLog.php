<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Lab 7 Part 1 Step 2: one entry of the polymorphic activity log
 * (table activity_logs). Written through the LogsActivity trait.
 */
class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'loggable_type', 'loggable_id', 'user_id', 'action', 'description', 'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /* ---------- Relationships ---------- */

    /** Supplier or PurchaseOrder (any model using the LogsActivity trait). */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Null when a scheduled command produced the entry. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ---------- Query scopes ---------- */

    public function scopeFor(Builder $query, Model $subject): Builder
    {
        return $query->where('loggable_type', $subject::class)
                     ->where('loggable_id', $subject->getKey());
    }

    public function scopeAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /* ---------- Presentation helpers ---------- */

    public function actionLabel(): string
    {
        return match ($this->action) {
            'created'   => 'Created',
            'updated'   => 'Updated',
            'deleted'   => 'Deleted',
            'restored'  => 'Restored',
            'submitted' => 'Submitted for approval',
            'approved'  => 'Approved',
            'rejected'  => 'Rejected',
            'sent'      => 'Sent to supplier',
            'received'  => 'Received',
            'cancelled' => 'Cancelled',
            default     => ucfirst($this->action),
        };
    }

    public function actionBadgeClass(): string
    {
        return match ($this->action) {
            'created', 'restored'  => 'bg-success',
            'updated'              => 'bg-info text-dark',
            'approved', 'received' => 'bg-primary',
            'rejected', 'deleted', 'cancelled' => 'bg-danger',
            default                => 'bg-secondary',
        };
    }
}
