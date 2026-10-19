<?php

namespace Database\Seeders;

use App\Models\AssignmentTransaction;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssignmentTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $ids     = Student::pluck('id', 'student_id');
        $adminId = User::value('id');

        // student_id, type, qty, reference, reason, notes, days ago, hour
        $samples = [
            ['2026-00101', 'assigned',   2, 'WST101-LAB5',  null, 'Laboratory Exercise 5',            9, 9],
            ['2026-00101', 'submitted',  2, 'WST101-LAB5',  null, 'Submitted on time',                7, 15],
            ['2026-00102', 'assigned',   2, 'SAD201-CASE2', null, 'Case study 2',                     8, 10],
            ['2026-00102', 'submitted',  2, 'SAD201-CASE2', null, 'Submitted late',                   5, 16],
            ['2026-00104', 'assigned',   1, 'ADS401-QUIZ3', null, 'Take-home quiz',                   6, 11],
            ['2026-00104', 'submitted',  1, 'ADS401-QUIZ3', null, null,                               4, 14],
            ['2026-00105', 'assigned',   3, 'SIA501-PROJ1', null, 'Project milestone 1',              7, 13],
            ['2026-00105', 'submitted',  3, 'SIA501-PROJ1', null, null,                               3, 10],
            ['2026-00110', 'assigned',   2, 'NS601-LAB2',   null, null,                               5, 9],
            ['2026-00110', 'submitted',  2, 'NS601-LAB2',   null, 'Perfect submission',               2, 15],
            ['2026-00123', 'adjustment', -1, 'ADJ-DEMO-01', 'Extension granted', 'Medical certificate', 3, 12],
            ['2026-00109', 'assigned',   1, 'DS701-HW4',    null, null,                               2, 9],
            ['2026-00109', 'submitted',  1, 'DS701-HW4',    null, null,                               1, 17],
            ['2026-00107', 'submitted',  1, 'WST101-LAB4',  null, 'Late submission accepted',         2, 14],
            ['2026-00107', 'assigned',   1, 'CC801-LAB1',   null, null,                               1, 10],
        ];

        foreach ($samples as [$code, $type, $qty, $ref, $reason, $notes, $daysAgo, $hour]) {
            $t = new AssignmentTransaction([
                'student_id'       => $ids[$code],
                'user_id'          => $adminId,
                'type'             => $type,
                'quantity'         => $type === 'submitted' ? -abs($qty) : $qty,
                'reference_number' => $ref,
                'reason'           => $reason,
                'notes'            => $notes,
            ]);
            $t->created_at = now()->subDays($daysAgo)->setTime($hour, 0);
            $t->save();   // the observer updates the student + sends notifications
        }
    }
}