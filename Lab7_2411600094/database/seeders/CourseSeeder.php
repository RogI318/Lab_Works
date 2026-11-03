<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        Course::query()->delete();

        $courses = [
            ['WST101', 'Web Systems and Technologies', 'Prof. Santos'],
            ['SAD201', 'Systems Analysis and Design', 'Prof. Reyes'],
            ['QM301', 'Quantitative Methods', 'Prof. Garcia'],
            ['ADS401', 'Advanced Database Systems', 'Prof. Cruz'],
            ['SIA501', 'Systems Integration and Architecture', 'Prof. Mendoza'],
            ['NS601', 'Network Security', 'Prof. Torres'],
            ['DS701', 'Data Structures and Algorithms', 'Prof. Lim'],
            ['CC801', 'Cloud Computing', 'Prof. Bautista'],
        ];

        foreach ($courses as [$code, $name, $instructor]) {
            Course::create(compact('code', 'name', 'instructor'));
        }
    }
}