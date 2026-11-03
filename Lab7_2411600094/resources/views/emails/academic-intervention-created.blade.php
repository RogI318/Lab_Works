
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Academic Intervention</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f6f9; padding:30px;">
    <div style="max-width:600px; margin:auto; background:white; padding:25px; border-radius:8px;">
        <h2 style="color:#0d6efd;">Student Portal</h2>

        <h3>New Academic Intervention Created</h3>

        <p>A new academic intervention has been recorded.</p>

        <p><strong>Reference:</strong> {{ $intervention->reference_number }}</p>
        <p><strong>Student:</strong> {{ $intervention->student->name ?? 'Unavailable' }}</p>
        <p><strong>Type:</strong> {{ $intervention->intervention_type }}</p>
        <p><strong>Priority:</strong> {{ $intervention->priority }}</p>
        <p><strong>Status:</strong> {{ ucfirst($intervention->status) }}</p>

        <p><strong>Description:</strong></p>
        <p>{{ $intervention->description ?? 'No description provided.' }}</p>

        <hr>
        <p style="color:#777;">This is an automated message from the Student Portal.</p>
    </div>
</body>
</html>
