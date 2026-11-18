<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        // Every brand used by ProductSeeder must be listed here
        $brands = [
            'Tropical Wood' => 'Coconut and local lumber for framing.',
            'Phil-Ply' => 'Plywood sheets for general and marine use.',
            'Pacific Roofing' => 'Roofing sheets and accessories.',
            'Republic Cement' => 'Portland cement and building materials.',
            'SteelAsia' => 'Steel bars and construction steel.',
            'Steelmax' => 'Nails, wire and general fasteners.',
            'Stanley' => 'Hand tools and measuring tools.',
            'Bosch' => 'Power tools and accessories.',
            'Tolsen' => 'Hand tools and hardware tools.',
            'Phelps Dodge' => 'Electrical wires and cables.',
            'Schneider' => 'Circuit breakers and electrical panels.',
            'Philips' => 'LED bulbs and lighting products.',
            'Neltex' => 'PVC pipes and fittings.',
            'Toyo' => 'Valves and plumbing fixtures.',
            'Pioneer' => 'Plumbing sealants, tapes and adhesives.',
            'Boysen' => 'Paints, primers and coatings.',
            'Hardin' => 'Paint brushes and painting tools.',
        ];

        foreach ($brands as $name => $description) {
            Brand::updateOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
