<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /** Twelve brands RogieMart Hardware actually carries. Runs before ProductSeeder. */
    public function run(): void
    {
        $brands = [
            ['Stanley',      'Hammers, tape measures, hand tools and general hardware.'],
            ['Makita',       'Cordless drills and professional power tools.'],
            ['Bosch',        'Power tools, blades and fastening accessories.'],
            ['DeWalt',       'Grinders, saws and heavy-duty job-site tools.'],
            ['Boysen',       'Latex and enamel paint for interiors and exteriors.'],
            ['Davies',       'Paint, primers and painting accessories.'],
            ['Philips',      'LED lamps and electrical lighting supplies.'],
            ['Firefly',      'Extension cords, electrical tape and wiring accessories.'],
            ['Omni Power',   'THHN wire and electrical cables by the roll.'],
            ['Plytech',      'Plywood, ceiling boards and lumber products.'],
            ['Marson',       'PVC pipes and fittings for water and drain lines.'],
            ['Dover',        'Gate valves, faucets and plumbing fixtures.'],
        ];

        foreach ($brands as [$name, $description]) {
            Brand::firstOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
