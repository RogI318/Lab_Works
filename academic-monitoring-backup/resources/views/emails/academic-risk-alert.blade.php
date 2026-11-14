
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Academic Risk Alert</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f6f9; padding:30px;">
    <div style="max-width:650px; margin:auto; background:white; padding:25px; border-radius:8px;">
        <h2 style="color:#dc3545;">Student Portal - Academic Risk Alert</h2>

        <p>The automated academic-risk check found students with heavy assignment backlogs.</p>

        <p><strong>Total students identified:</strong> {{ $students->count() }}</p>

        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f1f1f1;">
                    <th style="padding:10px; text-align:left;">Student ID</th>
                    <th style="padding:10px; text-align:left;">Name</th>
                    <th style="padding:10px; text-align:left;">Pending Assignments</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $student)
                    <tr>
                        <td style="padding:10px; border-bottom:1px solid #ddd;">
                            {{ $student->student_id }}
                        </td>
                        <td style="padding:10px; border-bottom:1px solid #ddd;">
                            {{ $student->name }}
                        </td>
                        <td style="padding:10px; border-bottom:1px solid #ddd;">
                            {{ $student->pending_assignments }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p style="margin-top:20px;">
            Please review these students and consider creating academic interventions.
        </p>

        <hr>
        <p style="color:#777;">Automated notification from the Student Portal.</p>
    </div>
</body>
</html>
