<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h2 { color: #0b3d91; margin-bottom: 2px; }
        p { margin-top: 0; color: #555555; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0b3d91; color: #ffffff; text-align: left; }
        th, td { border: 1px solid #cccccc; padding: 4px 6px; }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>
    <p>Student Portal &mdash; generated {{ now()->format('M d, Y h:i A') }} &mdash; {{ $interventions->count() }} record(s)</p>

    <table>
        <thead>
            <tr>
                <th>Reference</th>
                <th>Student</th>
                <th>Type</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Pending</th>
                <th>Created By</th>
                <th>Approved By</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse($interventions as $i)
                <tr>
                    <td>{{ $i->reference_number }}</td>
                    <td>{{ $i->student->name ?? 'Student unavailable' }} ({{ $i->student->student_id ?? '-' }})</td>
                    <td>{{ $i->intervention_type }}</td>
                    <td>{{ $i->priority }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $i->status)) }}</td>
                    <td>{{ $i->pending_assignments }}</td>
                    <td>{{ $i->creator->name ?? '-' }}</td>
                    <td>{{ $i->approver->name ?? '-' }}</td>
                    <td>{{ $i->created_at->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No intervention records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>