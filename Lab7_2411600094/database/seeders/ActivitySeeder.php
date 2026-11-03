<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Student;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $ids = Student::pluck('id', 'student_id');   // ['2026-00101' => 1, ...]

        // timestamp, student_id, activity, category, status
        $activities = [
            ['2026-09-14 14:30', '2026-00101', 'Grade posted for Web Systems and Technologies', 'Grades', 'Completed'],
            ['2026-09-14 11:15', '2026-00102', 'Attendance marked for Systems Analysis lecture', 'Attendance', 'Present'],
            ['2026-09-14 09:00', '2026-00103', 'Advisory note issued — GPA below probation threshold', 'Advisory', 'Pending'],
            ['2026-09-13 16:00', '2026-00104', 'Enrollment confirmed for next semester', 'Enrollment', 'Approved'],
            ['2026-09-13 14:20', '2026-00109', 'Missed Network Security laboratory session', 'Attendance', 'Absent'],
            ['2026-09-13 10:45', '2026-00110', 'Perfect score on Database Systems final exam', 'Grades', 'Completed'],
            ['2026-09-12 15:30', '2026-00115', 'Grade appeal submitted for Quantitative Methods', 'Grades', 'Pending'],
            ['2026-09-12 13:00', '2026-00107', 'Attendance flagged — below 75% threshold', 'Attendance', 'Absent'],
            ['2026-09-12 10:15', '2026-00120', 'Enrolled in Advanced Data Structures elective', 'Enrollment', 'Approved'],
            ['2026-09-11 16:45', '2026-00108', 'Grade posted for Systems Integration project', 'Grades', 'Completed'],
            ['2026-09-11 14:00', '2026-00123', 'Advisory meeting scheduled with academic counselor', 'Advisory', 'Pending'],
            ['2026-09-11 11:30', '2026-00114', 'Attendance marked for Web Systems laboratory', 'Attendance', 'Present'],
            ['2026-09-10 15:00', '2026-00119', 'Enrollment status changed to At Risk', 'Advisory', 'Pending'],
            ['2026-09-10 12:30', '2026-00128', 'Grade posted for Network Security midterm', 'Grades', 'Completed'],
            ['2026-09-10 09:00', '2026-00105', 'Requested program transfer to BS Data Science', 'Enrollment', 'Pending'],
            ['2026-09-09 15:20', '2026-00130', 'Attendance marked for Advanced Database lecture', 'Attendance', 'Present'],
            ['2026-09-09 11:00', '2026-00113', 'Grade posted for Quantitative Methods quiz', 'Grades', 'Completed'],
            ['2026-09-09 08:30', '2026-00127', 'Advisory note issued — attendance below threshold', 'Advisory', 'Pending'],
            ['2026-09-08 14:00', '2026-00125', 'Enrolled in Cloud Computing elective', 'Enrollment', 'Approved'],
            ['2026-09-08 10:00', '2026-00111', 'Grade appeal approved for Systems Analysis', 'Grades', 'Approved'],
        ];

        foreach ($activities as [$time, $code, $text, $category, $status]) {
            Activity::create([
                'student_id'  => $ids[$code],
                'activity'    => $text,
                'category'    => $category,
                'status'      => $status,
                'occurred_at' => $time,
            ]);
        }
    }
}