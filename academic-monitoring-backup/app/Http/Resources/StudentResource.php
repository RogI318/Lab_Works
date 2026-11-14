<?php

namespace App\Http\Resources;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'student_id'          => $this->student_id,
            'name'                => $this->name,
            'program'             => $this->program,
            'gpa'                 => (float) $this->gpa,
            'standing'            => $this->status,
            'pending_assignments' => $this->pending_assignments,
            'backlog_limit'       => Student::BACKLOG_LIMIT,
            'backlog_status'      => $this->backlog_status,
            'backlog_color'       => $this->backlog_color,
            'url'                 => route('students.show', $this->resource),
        ];
    }
}