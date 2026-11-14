<?php

namespace App\Models;

use App\Observers\AssignmentTransactionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([AssignmentTransactionObserver::class])]
class AssignmentTransaction extends Model
{
    public const TYPES = [
        'assigned'   => 'Assigned',
        'submitted'  => 'Submitted',
        'adjustment' => 'Adjustment',
    ];

    public const ADJUSTMENT_REASONS = [
        'Extension granted',
        'Late submission accepted',
        'Excused by instructor',
        'Recount / correction',
        'Course dropped',
    ];

    protected $fillable = [
        'student_id', 'user_id', 'type', 'quantity',
        'reference_number', 'reason', 'notes',
    ];

    protected $casts = ['quantity' => 'integer'];

    /* ---------- Relationships ---------- */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ---------- Query scopes ---------- */
    public function scopeAssigned($query)    { return $query->where('type', 'assigned'); }
    public function scopeSubmitted($query)   { return $query->where('type', 'submitted'); }
    public function scopeAdjustments($query) { return $query->where('type', 'adjustment'); }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /* ---------- Helpers ---------- */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function getQuantityLabelAttribute(): string
    {
        return ($this->quantity > 0 ? '+' : '') . $this->quantity;
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            'assigned'   => 'bg-primary',
            'submitted'  => 'bg-success',
            'adjustment' => 'bg-info text-dark',
            default      => 'bg-secondary',
        };
    }
}