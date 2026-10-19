<?php

namespace Database\Seeders;

use App\Models\AssignmentTransaction;
use App\Models\User;
use App\Models\Activity;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        AssignmentTransaction::query()->delete();
        Activity::query()->delete();
        Student::query()->delete();

        $adminId = User::value('id');

        // student_id, name, program, year, units, gpa, attendance, pending assignments
        $students = [
            ['2026-00101', 'Juan Dela Cruz', 'BS Computer Science', 3, 18, 3.75, 94, 1],
            ['2026-00102', 'Maria Santos', 'BS Information Technology', 2, 15, 2.10, 68, 4],
            ['2026-00103', 'Pedro Reyes', 'BS Information Systems', 4, 21, 1.65, 55, 5],
            ['2026-00104', 'Ana Villanueva', 'BS Data Science', 1, 18, 3.85, 98, 0],
            ['2026-00105', 'Jose Mendoza', 'BS Computer Science', 3, 18, 3.20, 88, 2],
            ['2026-00106', 'Liza Ramos', 'BS Information Technology', 4, 21, 2.85, 82, 3],
            ['2026-00107', 'Carlos Bautista', 'BS Information Systems', 2, 15, 2.40, 72, 4],
            ['2026-00108', 'Sofia Garcia', 'BS Data Science', 3, 18, 3.55, 91, 1],
            ['2026-00109', 'Miguel Torres', 'BS Computer Science', 1, 18, 1.90, 60, 5],
            ['2026-00110', 'Isabella Cruz', 'BS Information Technology', 3, 18, 3.95, 99, 0],
            ['2026-00111', 'Rafael Aquino', 'BS Information Systems', 4, 21, 2.65, 78, 3],
            ['2026-00112', 'Camila Navarro', 'BS Data Science', 2, 15, 3.10, 85, 2],
            ['2026-00113', 'Diego Fernandez', 'BS Computer Science', 4, 21, 2.30, 70, 4],
            ['2026-00114', 'Valeria Lim', 'BS Information Technology', 1, 18, 3.65, 95, 1],
            ['2026-00115', 'Andres Castillo', 'BS Information Systems', 3, 18, 1.55, 52, 5],
            ['2026-00116', 'Natalia Domingo', 'BS Data Science', 4, 21, 3.40, 89, 1],
            ['2026-00117', 'Gabriel Salazar', 'BS Computer Science', 2, 15, 2.75, 80, 3],
            ['2026-00118', 'Beatriz Mercado', 'BS Information Technology', 3, 18, 3.25, 87, 2],
            ['2026-00119', 'Emilio Pascual', 'BS Information Systems', 1, 18, 2.05, 66, 4],
            ['2026-00120', 'Daniela Rivera', 'BS Data Science', 3, 18, 3.80, 96, 0],
            ['2026-00121', 'Hector Soriano', 'BS Computer Science', 4, 21, 2.55, 74, 3],
            ['2026-00122', 'Patricia Velasco', 'BS Information Technology', 2, 15, 3.30, 90, 1],
            ['2026-00123', 'Ricardo Ong', 'BS Information Systems', 3, 18, 1.85, 58, 5],
            ['2026-00124', 'Teresa Lorenzo', 'BS Data Science', 1, 18, 3.50, 93, 1],
            ['2026-00125', 'Fernando Alonzo', 'BS Computer Science', 2, 15, 2.95, 84, 2],
            ['2026-00126', 'Gabriela Ramos', 'BS Information Technology', 4, 21, 3.15, 86, 2],
            ['2026-00127', 'Mario Espinosa', 'BS Information Systems', 2, 15, 2.20, 69, 4],
            ['2026-00128', 'Lucia Yulo', 'BS Data Science', 3, 18, 3.70, 97, 0],
            ['2026-00129', 'Enrique Villa', 'BS Computer Science', 1, 18, 2.35, 71, 4],
            ['2026-00130', 'Rosario Delgado', 'BS Information Technology', 3, 18, 3.60, 92, 1],
        ];

        foreach ($students as [$id, $name, $program, $year, $units, $gpa, $attendance, $pending]) {
            $student = Student::create([
                'student_id' => $id,
                'name' => $name,
                'program' => $program,
                'year_level' => $year,
                'units' => $units,
                'gpa' => $gpa,
                'attendance' => $attendance,
                'pending_assignments' => 0,                       // the ledger sets this
                'status' => Student::computeStatus($gpa),
            ]);

            // Opening balance from the Lab 4 data, recorded as a transaction 14 days ago
            if ($pending > 0) {
                $opening = new AssignmentTransaction([
                    'student_id' => $student->id,
                    'user_id' => $adminId,
                    'type' => 'assigned',
                    'quantity' => $pending,
                    'reference_number' => 'OPENING-' . $id,
                    'notes' => 'Opening balance imported from the Lab 4 data.',
                ]);
                $opening->created_at = now()->subDays(14)->setTime(8, 0);
                $opening->save();
            }
        }
    }
}