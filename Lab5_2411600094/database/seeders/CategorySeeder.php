<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /** The six sections of the campus supply store. Runs before ProductSeeder. */
    public function run(): void
    {
        $categories = [
            ['Books & Modules',     'Textbooks, workbooks, department modules and board-exam reviewers.'],
            ['Stationery',          'Notebooks, pad paper, ballpens, markers, pencils and classroom consumables.'],
            ['Lab Equipment',       'Gloves, wipes, masks and the consumables issued to science laboratory sections.'],
            ['IT Peripherals',      'Calculators, flash drives, mouse devices, keyboards and printer supplies.'],
            ['Uniforms & Merch',    'Lab gowns, PE uniforms, ID lanyards and school merchandise.'],
            ['Sports & PE',         'Balls and equipment issued for physical education classes and intramurals.'],
        ];

        foreach ($categories as [$name, $description]) {
            Category::firstOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
