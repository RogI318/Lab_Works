<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'date'             => $this->created_at->format('M d, h:i A'),
            'student_name'     => $this->student->name ?? '—',
            'student_url'      => $this->student ? route('students.show', $this->student) : null,
            'type'             => $this->type,
            'type_label'       => $this->type_label,
            'type_badge'       => $this->typeBadgeClass(),
            'quantity'         => $this->quantity,
            'quantity_label'   => $this->quantity_label,
            'reference_number' => $this->reference_number,
            'recorded_by'      => $this->user->name ?? '—',
            'url'              => route('assignments.show', $this->resource),
        ];
    }
}