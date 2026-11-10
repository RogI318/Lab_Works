<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /** Ten brands the campus supply store actually carries. Runs before ProductSeeder. */
    public function run(): void
    {
        $brands = [
            ['Pilot',                          'Ballpens, markers and highlighters for classroom use.'],
            ['Faber-Castell',                  'Pencils, erasers, sharpeners and art-craft sets.'],
            ['New Research Educational Supplies', 'School notebooks, pad paper and curriculum modules.'],
            ['Tombow',                         'Mechanical pencils and correction tape.'],
            ['Casio',                          'Scientific calculators approved for board examinations.'],
            ['HP',                             'Bond paper, flash drives and printer consumables.'],
            ['Logitech',                       'Wireless mouse devices and keyboards for the computer labs.'],
            ['Kimberly-Clark Professional',    'Examination gloves, lens wipes and face masks for the science labs.'],
            ['Catseye Sportswear',             'PE uniforms, laboratory gowns, lanyards and school merchandise.'],
            ['Molten',                         'Game and training balls for physical education and intramurals.'],
        ];

        foreach ($brands as [$name, $description]) {
            Brand::firstOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
