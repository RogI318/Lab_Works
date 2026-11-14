<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicIntervention extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'created_by',
        'approved_by',
        'reference_number',
        'intervention_type',
        'priority',
        'status',
        'pending_assignments',
        'description',
        'action_plan',
        'rejection_reason',
        'approved_at',
        'scheduled_at',
        'completed_at',
    ];

    protected $casts = [
        'pending_assignments' => 'integer',
        'approved_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

  // Relationships

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    //Query Scopes

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            'pending',
            'approved',
            'in_progress'
        ]);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [
            'High',
            'Critical'
        ]);
    }

    // Helper Methods

    public static function generateReferenceNumber(): string
    {
        return 'AI-' . now()->format('YmdHis')
            . '-' . strtoupper(substr(uniqid(), -5));
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeCompleted(): bool
    {
        return in_array($this->status, [
            'approved',
            'in_progress'
        ]);
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'Critical' => 'danger',
            'High' => 'warning',
            'Medium' => 'info',
            'Low' => 'success',
            default => 'secondary',
        };
    }
}
