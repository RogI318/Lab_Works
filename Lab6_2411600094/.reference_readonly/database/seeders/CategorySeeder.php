<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Lumber' => 'Wood, plywood and lumber for framing and formworks.',
            'Hardware' => 'Nails, wire, cement, steel bars and fasteners.',
            'Tools' => 'Hand tools, power tools and measuring tools.',
            'Electrical' => 'Wires, breakers, bulbs and electrical supplies.',
            'Plumbing' => 'Pipes, fittings and valves for water lines.',
            'Paint' => 'Paints, brushes and finishing supplies.',
        ];

        foreach ($categories as $name => $description) {
            Category::updateOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
