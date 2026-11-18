<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Sample hardware store products (prices in PHP)
        $products = [

            // ---------- Lumber ----------
            ['name' => 'Coco Lumber 2x2x8 ft', 'sku' => 'LUM-001', 'category' => 'Lumber', 'brand' => 'Tropical Wood',
             'description' => 'Coconut lumber, good for framing and scaffolding.',
             'quantity' => 120, 'reorder_level' => 30, 'unit_price' => 85.00, 'supplier' => 'Mindanao Timber Supply'],

            ['name' => 'Marine Plywood 1/2 in x 4x8 ft', 'sku' => 'LUM-002', 'category' => 'Lumber', 'brand' => 'Phil-Ply',
             'description' => 'Water-resistant plywood sheet.',
             'quantity' => 12, 'reorder_level' => 15, 'unit_price' => 1250.00, 'supplier' => 'Mindanao Timber Supply'],

            ['name' => 'Ordinary Plywood 3/4 in x 4x8 ft', 'sku' => 'LUM-003', 'category' => 'Lumber', 'brand' => 'Phil-Ply',
             'description' => 'General-purpose plywood sheet.',
             'quantity' => 0, 'reorder_level' => 20, 'unit_price' => 980.00, 'supplier' => 'Mindanao Timber Supply'],

            ['name' => 'Good Lumber 2x3x10 ft', 'sku' => 'LUM-004', 'category' => 'Lumber', 'brand' => 'Tropical Wood',
             'description' => 'Kiln-dried lumber for roof framing.',
             'quantity' => 65, 'reorder_level' => 25, 'unit_price' => 210.00, 'supplier' => 'Visayas Wood Traders'],

            // ---------- Hardware ----------
            ['name' => 'Common Wire Nails 3 in (1 kg)', 'sku' => 'HW-001', 'category' => 'Hardware', 'brand' => 'Steelmax',
             'description' => 'Steel wire nails, sold per kilo.',
             'quantity' => 200, 'reorder_level' => 50, 'unit_price' => 95.00, 'supplier' => 'Cebu Steel Works'],

            ['name' => 'Portland Cement 40 kg', 'sku' => 'HW-002', 'category' => 'Hardware', 'brand' => 'Republic Cement',
             'description' => 'Type 1 Portland cement bag.',
             'quantity' => 18, 'reorder_level' => 40, 'unit_price' => 245.00, 'supplier' => 'Island Cement Corp.'],

            ['name' => 'Deformed Steel Bar 10 mm x 6 m', 'sku' => 'HW-003', 'category' => 'Hardware', 'brand' => 'SteelAsia',
             'description' => 'Rebar for concrete reinforcement.',
             'quantity' => 150, 'reorder_level' => 60, 'unit_price' => 185.00, 'supplier' => 'Cebu Steel Works'],

            ['name' => 'Corrugated GI Sheet 8 ft', 'sku' => 'HW-004', 'category' => 'Hardware', 'brand' => 'Pacific Roofing',
             'description' => 'Gauge 26 roofing sheet.',
             'quantity' => 45, 'reorder_level' => 20, 'unit_price' => 420.00, 'supplier' => 'Cebu Steel Works'],

            // ---------- Tools ----------
            ['name' => 'Claw Hammer 16 oz', 'sku' => 'TL-001', 'category' => 'Tools', 'brand' => 'Stanley',
             'description' => 'Steel claw hammer with rubber grip.',
             'quantity' => 30, 'reorder_level' => 10, 'unit_price' => 280.00, 'supplier' => 'ToolMaster Trading'],

            ['name' => 'Cordless Drill 12V', 'sku' => 'TL-002', 'category' => 'Tools', 'brand' => 'Bosch',
             'description' => 'Cordless drill with battery and charger.',
             'quantity' => 5, 'reorder_level' => 6, 'unit_price' => 2450.00, 'supplier' => 'ToolMaster Trading'],

            ['name' => 'Hand Saw 24 in', 'sku' => 'TL-003', 'category' => 'Tools', 'brand' => 'Tolsen',
             'description' => 'Hardened-teeth wood saw.',
             'quantity' => 22, 'reorder_level' => 8, 'unit_price' => 320.00, 'supplier' => 'ToolMaster Trading'],

            ['name' => 'Steel Measuring Tape 5 m', 'sku' => 'TL-004', 'category' => 'Tools', 'brand' => 'Stanley',
             'description' => 'Locking steel tape measure.',
             'quantity' => 0, 'reorder_level' => 12, 'unit_price' => 150.00, 'supplier' => 'ToolMaster Trading'],

            // ---------- Electrical ----------
            ['name' => 'THHN Wire 2.0 mm (75 m roll)', 'sku' => 'EL-001', 'category' => 'Electrical', 'brand' => 'Phelps Dodge',
             'description' => 'Stranded copper wire roll.',
             'quantity' => 25, 'reorder_level' => 10, 'unit_price' => 2800.00, 'supplier' => 'Bright Electrical Supply'],

            ['name' => 'Circuit Breaker 20A', 'sku' => 'EL-002', 'category' => 'Electrical', 'brand' => 'Schneider',
             'description' => 'Single-pole plug-in breaker.',
             'quantity' => 9, 'reorder_level' => 10, 'unit_price' => 295.00, 'supplier' => 'Bright Electrical Supply'],

            ['name' => 'LED Bulb 9W Daylight', 'sku' => 'EL-003', 'category' => 'Electrical', 'brand' => 'Philips',
             'description' => 'Energy-saving LED bulb, E27 base.',
             'quantity' => 150, 'reorder_level' => 40, 'unit_price' => 65.00, 'supplier' => 'Bright Electrical Supply'],

            // ---------- Plumbing ----------
            ['name' => 'PVC Pipe 1/2 in x 10 ft', 'sku' => 'PL-001', 'category' => 'Plumbing', 'brand' => 'Neltex',
             'description' => 'Schedule 40 PVC pipe.',
             'quantity' => 80, 'reorder_level' => 25, 'unit_price' => 120.00, 'supplier' => 'AquaFlow Plumbing Depot'],

            ['name' => 'Gate Valve 3/4 in', 'sku' => 'PL-002', 'category' => 'Plumbing', 'brand' => 'Toyo',
             'description' => 'Brass gate valve.',
             'quantity' => 7, 'reorder_level' => 8, 'unit_price' => 385.00, 'supplier' => 'AquaFlow Plumbing Depot'],

            ['name' => 'Teflon Tape 3/4 in', 'sku' => 'PL-003', 'category' => 'Plumbing', 'brand' => 'Pioneer',
             'description' => 'Thread seal tape.',
             'quantity' => 100, 'reorder_level' => 30, 'unit_price' => 18.00, 'supplier' => 'AquaFlow Plumbing Depot'],

            // ---------- Paint ----------
            ['name' => 'Latex Paint White 4 L', 'sku' => 'PT-001', 'category' => 'Paint', 'brand' => 'Boysen',
             'description' => 'Interior/exterior flat latex paint.',
             'quantity' => 35, 'reorder_level' => 15, 'unit_price' => 780.00, 'supplier' => 'ColorWorks Paint Co.'],

            ['name' => 'Epoxy Primer Gray 1 L', 'sku' => 'PT-002', 'category' => 'Paint', 'brand' => 'Boysen',
             'description' => 'Primer for metal surfaces.',
             'quantity' => 6, 'reorder_level' => 10, 'unit_price' => 560.00, 'supplier' => 'ColorWorks Paint Co.'],

            ['name' => 'Paint Brush 3 in', 'sku' => 'PT-003', 'category' => 'Paint', 'brand' => 'Hardin',
             'description' => 'Bristle paint brush.',
             'quantity' => 60, 'reorder_level' => 20, 'unit_price' => 75.00, 'supplier' => 'ColorWorks Paint Co.'],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
