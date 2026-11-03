
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Intervention Approved</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f6f9; padding:30px;">
    <div style="max-width:600px; margin:auto; background:white; padding:25px; border-radius:8px;">
        <h2 style="color:#198754;">Student Portal</h2>

        <h3>Academic Intervention Approved</h3>

        <p>An academic intervention has been approved successfully.</p>

        <p><strong>Reference:</strong> {{ $intervention->reference_number }}</p>
        <p><strong>Student:</strong> {{ $intervention->student->name ?? 'Unavailable' }}</p>
        <p><strong>Type:</strong> {{ $intervention->intervention_type }}</p>
        <p><strong>Priority:</strong> {{ $intervention->priority }}</p>
        <p><strong>Approved At:</strong> {{ $intervention->approved_at?->format('M d, Y h:i A') }}</p>

        <hr>
        <p style="color:#777;">This is an automated message from the Student Portal.</p>
    </div>
</body>
</html>
