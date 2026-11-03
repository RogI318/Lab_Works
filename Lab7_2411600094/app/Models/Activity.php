<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    protected $fillable = ['student_id', 'activity', 'category', 'status', 'occurred_at'];

    protected $casts = ['occurred_at' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function categoryBadgeClass(): string
    {
        return match ($this->category) {
            'Grades'     => 'bg-primary',
            'Attendance' => 'bg-info text-dark',
            'Enrollment' => 'bg-success',
            'Advisory'   => 'bg-warning text-dark',
            default      => 'bg-secondary',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'Completed', 'Approved', 'Present' => 'bg-success',
            'Pending'                          => 'bg-warning text-dark',
            'Absent'                           => 'bg-danger',
            default                            => 'bg-secondary',
        };
    }
}