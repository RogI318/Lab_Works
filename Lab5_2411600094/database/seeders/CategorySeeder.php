<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /** The six sections of RogieMart Hardware. Runs before ProductSeeder. */
    public function run(): void
    {
        $categories = [
            ['Lumber',    'Coco lumber, plywood and ceiling boards for framing, formwork and roofing.'],
            ['Hardware',  'Nails, GI sheets, bolts, hinges and other fasteners and building hardware.'],
            ['Tools',     'Hand tools and power tools used by the carpentry and installation crews.'],
            ['Electrical','Wires, extension cords, lamps and electrical supplies for site wiring.'],
            ['Plumbing',  'PVC pipes and fittings, valves and plumbing materials for water lines.'],
            ['Paint',     'Latex and enamel paint, primers and painting accessories for finishing work.'],
        ];

        foreach ($categories as [$name, $description]) {
            Category::firstOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
