<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Opening stock for the Campus Supplies Inventory.
     * Each product stores its quantity on hand directly; the mix below gives
     * healthy, low-stock and out-of-stock items for the dashboard alerts.
     */
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');
        $brands     = Brand::pluck('id', 'name');

        $nres     = 'New Research Educational Supplies';
        $kcc      = 'Kimberly-Clark Professional';

        $products = [
            // ---------- Books & Modules ----------
            ['name' => 'General Biology Module (Senior High)', 'sku' => 'BKM-BIO-1001',
             'category' => 'Books & Modules', 'brand' => $nres, 'supplier' => 'NRES Distribution Center',
             'price' => 385.00, 'reorder' => 15, 'quantity' => 42,
             'description' => 'Curriculum module issued to all Grade 11 STEM sections.'],
            ['name' => 'College Physics Workbook', 'sku' => 'BKM-PHY-1002',
             'category' => 'Books & Modules', 'brand' => $nres, 'supplier' => 'NRES Distribution Center',
             'price' => 460.00, 'reorder' => 12, 'quantity' => 9,
             'description' => 'Workbook for the physics laboratory course; low stock flagged.'],
            ['name' => 'Mathematics Reviewer (Grade 12)', 'sku' => 'BKM-MTH-1003',
             'category' => 'Books & Modules', 'brand' => $nres, 'supplier' => 'NRES Distribution Center',
             'price' => 245.00, 'reorder' => 20, 'quantity' => 0,
             'description' => 'Board-exam reviewer; completely released to the reviewing sections.'],

            // ---------- Stationery ----------
            ['name' => 'Spiral Notebook 100 pages A5', 'sku' => 'STN-NB-2001',
             'category' => 'Stationery', 'brand' => $nres, 'supplier' => 'Ritex Paper Corporation',
             'price' => 89.00, 'reorder' => 40, 'quantity' => 120,
             'description' => 'Double-line spiral notebook distributed during enrolment.'],
            ['name' => 'Long Pad Paper 8.5x13 (50 sheets)', 'sku' => 'STN-PD-2002',
             'category' => 'Stationery', 'brand' => $nres, 'supplier' => 'Ritex Paper Corporation',
             'price' => 65.50, 'reorder' => 50, 'quantity' => 38,
             'description' => 'Used for examinations and take-home quizzes.'],
            ['name' => 'Training Ballpen (box of 12)', 'sku' => 'STN-BP-2003',
             'category' => 'Stationery', 'brand' => 'Pilot', 'supplier' => 'Station Hub Trading',
             'price' => 168.00, 'reorder' => 30, 'quantity' => 96,
             'description' => 'Blue ballpens released per section, boxed.'],
            ['name' => 'Whiteboard Marker Fine (assorted)', 'sku' => 'STN-WM-2004',
             'category' => 'Stationery', 'brand' => 'Pilot', 'supplier' => 'Station Hub Trading',
             'price' => 45.00, 'reorder' => 24, 'quantity' => 18,
             'description' => 'Classroom markers, fast-moving during examination weeks.'],
            ['name' => 'Mechanical Pencil 0.5mm', 'sku' => 'STN-MP-2005',
             'category' => 'Stationery', 'brand' => 'Tombow', 'supplier' => 'Station Hub Trading',
             'price' => 95.00, 'reorder' => 20, 'quantity' => 54,
             'description' => 'Approved for engineering drawing subjects.'],
            ['name' => 'Correction Tape 5mm', 'sku' => 'STN-CT-2006',
             'category' => 'Stationery', 'brand' => 'Tombow', 'supplier' => 'Station Hub Trading',
             'price' => 78.00, 'reorder' => 15, 'quantity' => 7,
             'description' => 'Single refill tape dispenser.'],
            ['name' => 'Colored Pencil Set 12 colors', 'sku' => 'STN-CP-2007',
             'category' => 'Stationery', 'brand' => 'Faber-Castell', 'supplier' => 'ArtsMart Trading',
             'price' => 210.00, 'reorder' => 12, 'quantity' => 28,
             'description' => 'Bought per section for the fine-arts module.'],
            ['name' => 'Bond Paper 8.5x14 Ivory (ream)', 'sku' => 'STN-BD-2008',
             'category' => 'Stationery', 'brand' => 'HP', 'supplier' => 'Office World Supply',
             'price' => 255.00, 'reorder' => 12, 'quantity' => 6,
             'description' => 'Ream of 500 sheets for thesis and board papers.'],

            // ---------- Lab Equipment ----------
            ['name' => 'Nitrile Examination Gloves (box of 100)', 'sku' => 'LAB-GL-3001',
             'category' => 'Lab Equipment', 'brand' => $kcc, 'supplier' => 'Medilab Supplies Inc.',
             'price' => 620.00, 'reorder' => 10, 'quantity' => 3,
             'description' => 'Powder-free gloves for the anatomy and microbiology labs.'],
            ['name' => 'Laboratory Lens Wipes (pack of 250)', 'sku' => 'LAB-WP-3002',
             'category' => 'Lab Equipment', 'brand' => $kcc, 'supplier' => 'Medilab Supplies Inc.',
             'price' => 285.00, 'reorder' => 12, 'quantity' => 34,
             'description' => 'Cleaning wipes for microscope lenses and glassware.'],
            ['name' => 'Face Mask 3-Ply (box of 50)', 'sku' => 'LAB-FM-3003',
             'category' => 'Lab Equipment', 'brand' => $kcc, 'supplier' => 'Medilab Supplies Inc.',
             'price' => 165.00, 'reorder' => 15, 'quantity' => 0,
             'description' => 'Required for every laboratory session; currently depleted.'],

            // ---------- IT Peripherals ----------
            ['name' => 'Scientific Calculator FX-991EX', 'sku' => 'ITP-CL-4001',
             'category' => 'IT Peripherals', 'brand' => 'Casio', 'supplier' => 'Edvantage Technology',
             'price' => 2150.00, 'reorder' => 6, 'quantity' => 11,
             'description' => 'Rental units for students without their own calculator.'],
            ['name' => 'USB Flash Drive 32GB', 'sku' => 'ITP-USB-4002',
             'category' => 'IT Peripherals', 'brand' => 'HP', 'supplier' => 'Edvantage Technology',
             'price' => 349.00, 'reorder' => 15, 'quantity' => 62,
             'description' => 'Distribution media for thesis files and reviewers.'],
            ['name' => 'Optical Wireless Mouse', 'sku' => 'ITP-MS-4003',
             'category' => 'IT Peripherals', 'brand' => 'Logitech', 'supplier' => 'Edvantage Technology',
             'price' => 549.00, 'reorder' => 10, 'quantity' => 5,
             'description' => 'Replacements for the computer laboratory.'],
            ['name' => 'Wireless Keyboard (full size)', 'sku' => 'ITP-KB-4004',
             'category' => 'IT Peripherals', 'brand' => 'Logitech', 'supplier' => 'Edvantage Technology',
             'price' => 890.00, 'reorder' => 8, 'quantity' => 0,
             'description' => 'Issued to the typewriting and computer literacy classes.'],
            ['name' => 'LaserJet Toner Cartridge 105A', 'sku' => 'ITP-TN-4005',
             'category' => 'IT Peripherals', 'brand' => 'HP', 'supplier' => 'Office World Supply',
             'price' => 2980.00, 'reorder' => 4, 'quantity' => 9,
             'description' => 'Keeps the registrar and department printers running.'],

            // ---------- Uniforms & Merch ----------
            ['name' => 'Laboratory Gown (student size)', 'sku' => 'UNI-GW-5001',
             'category' => 'Uniforms & Merch', 'brand' => 'Catseye Sportswear', 'supplier' => 'Texline Uniform Services',
             'price' => 395.00, 'reorder' => 20, 'quantity' => 46,
             'description' => 'White gown required for all laboratory subjects.'],
            ['name' => 'PE Uniform Shirt (Junior)', 'sku' => 'UNI-PE-5002',
             'category' => 'Uniforms & Merch', 'brand' => 'Catseye Sportswear', 'supplier' => 'Texline Uniform Services',
             'price' => 320.00, 'reorder' => 25, 'quantity' => 12,
             'description' => 'Reorder required before the intramurals week.'],
            ['name' => 'Student ID Lanyard (school logo)', 'sku' => 'UNI-LN-5003',
             'category' => 'Uniforms & Merch', 'brand' => 'Catseye Sportswear', 'supplier' => 'IdCard PH',
             'price' => 95.00, 'reorder' => 30, 'quantity' => 88,
             'description' => 'Woven lanyard issued with the official student ID.'],

            // ---------- Sports & PE ----------
            ['name' => 'Basketball Size 7 (game)', 'sku' => 'SPT-BB-6001',
             'category' => 'Sports & PE', 'brand' => 'Molten', 'supplier' => 'Sportsphere.ph',
             'price' => 1850.00, 'reorder' => 6, 'quantity' => 14,
             'description' => 'Official game ball for intramurals and PE classes.'],
            ['name' => 'Volleyball Size 5 (training)', 'sku' => 'SPT-VB-6002',
             'category' => 'Sports & PE', 'brand' => 'Molten', 'supplier' => 'Sportsphere.ph',
             'price' => 1250.00, 'reorder' => 8, 'quantity' => 2,
             'description' => 'Training ball; two units left after the volleyball clinic.'],
            ['name' => 'Sepak Takraw Ball (rattan)', 'sku' => 'SPT-ST-6003',
             'category' => 'Sports & PE', 'brand' => 'Molten', 'supplier' => 'Sportsphere.ph',
             'price' => 780.00, 'reorder' => 10, 'quantity' => 0,
             'description' => 'Fully issued to the takraw team before the district meet.'],
        ];

        foreach ($products as $item) {
            Product::create([
                'name'          => $item['name'],
                'sku'           => $item['sku'],
                'description'   => $item['description'],
                'brand_id'      => $brands[$item['brand']],
                'category_id'   => $categories[$item['category']],
                'quantity'      => $item['quantity'],
                'reorder_level' => $item['reorder'],
                'unit_price'    => $item['price'],
                'supplier'      => $item['supplier'],
            ]);
        }
    }
}
