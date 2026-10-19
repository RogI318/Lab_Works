<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    public const PROGRAMS = [
        'BS Computer Science',
        'BS Information Technology',
        'BS Information Systems',
        'BS Data Science',
    ];

    public const BACKLOG_LIMIT = 5;    // pending >= this  => "Heavy Backlog" (like reorder level)
    public const MAX_PENDING   = 20;

    protected $fillable = [
        'student_id', 'name', 'program', 'year_level', 'units',
        'gpa', 'attendance', 'pending_assignments', 'status',
    ];

    protected $casts = [
        'gpa'                 => 'decimal:2',
        'year_level'          => 'integer',
        'units'               => 'integer',
        'attendance'          => 'integer',
        'pending_assignments' => 'integer',
    ];

    /* ---------- Relationships ---------- */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AssignmentTransaction::class);
    }

    public function latestTransaction(): HasOne
    {
        return $this->hasOne(AssignmentTransaction::class)->latestOfMany();
    }

    /* ---------- Academic standing (same rules as Lab 4) ---------- */
    public static function computeStatus(float $gpa): string
    {
        if ($gpa >= 2.5)  return 'Good Standing';
        if ($gpa >= 1.75) return 'At Risk';
        return 'Probation';
    }

    public function isAtRisk(): bool        { return $this->status === 'At Risk'; }
    public function isOnProbation(): bool   { return $this->status === 'Probation'; }
    public function isGoodStanding(): bool  { return $this->status === 'Good Standing'; }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'Good Standing' => 'bg-success',
            'At Risk'       => 'bg-warning text-dark',
            'Probation'     => 'bg-danger',
            default         => 'bg-secondary',
        };
    }

    public function gpaColorClass(): string
    {
        if ($this->gpa < 1.75) return 'text-danger';
        if ($this->gpa < 2.50) return 'text-warning';
        return 'text-success';
    }

    /* ---------- Backlog helpers (Lab 6) ---------- */
    public function isHeavyBacklog(): bool
    {
        return $this->pending_assignments >= self::BACKLOG_LIMIT;
    }

    public function isBacklogClear(): bool
    {
        return $this->pending_assignments <= 0;
    }

    public function getBacklogStatusAttribute(): string
    {
        if ($this->isBacklogClear())  return 'Clear';
        if ($this->isHeavyBacklog())  return 'Heavy Backlog';
        return 'Manageable';
    }

    /** Bootstrap color name: success / warning / danger */
    public function getBacklogColorAttribute(): string
    {
        if ($this->isBacklogClear())  return 'success';
        if ($this->isHeavyBacklog())  return 'danger';
        return 'warning';
    }

    /* ---------- Query scopes ---------- */
    public function scopeHeavyBacklog($query)
    {
        return $query->where('pending_assignments', '>=', self::BACKLOG_LIMIT);
    }

    public function scopeClearBacklog($query)
    {
        return $query->where('pending_assignments', '<=', 0);
    }

    public function scopeInProgram($query, string $program)
    {
        return $query->where('program', $program);
    }

    /** Standing is always recomputed from the GPA whenever a student is saved. */
    protected static function booted(): void
    {
        static::saving(function (Student $student) {
            $student->status = self::computeStatus((float) $student->gpa);
        });
    }
}