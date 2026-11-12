<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Opening stock for the RogieMart Hardware Inventory.
     * "on_hand" is the figure each item must end at; "issued" are units already
     * sold or drawn for jobs, so the receiving quantity is on_hand + issued. The mix
     * below gives healthy, low-stock and out-of-stock items for the dashboard to report.
     */
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');
        $brands     = Brand::pluck('id', 'name');
        $userId     = User::value('id');

        $stanley  = 'Stanley';
        $plytech  = 'Plytech';
        $marson   = 'Marson';
        $dover    = 'Dover';
        $firefly  = 'Firefly';

        $products = [
            // ---------- Lumber ----------
            ['name' => 'Coco Lumber 2x2x10', 'sku' => 'LMB-COC-1001',
             'category' => 'Lumber', 'brand' => $plytech, 'supplier' => 'Metro Lumber Supply',
             'price' => 185.00, 'reorder' => 30, 'on_hand' => 84, 'issued' => 26,
             'description' => 'Saw hardwood for roof battens, framing and formwork.'],
            ['name' => 'Marine Plywood 1/2" x 2x4', 'sku' => 'LMB-PLY-1002',
             'category' => 'Lumber', 'brand' => $plytech, 'supplier' => 'Metro Lumber Supply',
             'price' => 620.00, 'reorder' => 15, 'on_hand' => 40, 'issued' => 12,
             'description' => 'Water-resistant plywood for concrete formwork and cabinetry.'],
            ['name' => 'Ceiling Board 1/4" x 4x8', 'sku' => 'LMB-CEI-1003',
             'category' => 'Lumber', 'brand' => $plytech, 'supplier' => 'Northside Lumber Yard',
             'price' => 395.00, 'reorder' => 20, 'on_hand' => 8, 'issued' => 14,
             'description' => 'Interior ceiling panel; running low, reorder before the next finishing job.'],

            // ---------- Hardware ----------
            ['name' => 'Common Wire Nails 3" (1 kg)', 'sku' => 'HDW-NL-2001',
             'category' => 'Hardware', 'brand' => $stanley, 'supplier' => 'Hardware Depot Trading',
             'price' => 145.00, 'reorder' => 40, 'on_hand' => 160, 'issued' => 55,
             'description' => 'Bright nails for general carpentry, priced per kilo.'],
            ['name' => 'GI Corrugated Roof Sheet 0.35mm x 3ft', 'sku' => 'HDW-GI-2002',
             'category' => 'Hardware', 'brand' => $plytech, 'supplier' => 'Metro Lumber Supply',
             'price' => 480.00, 'reorder' => 20, 'on_hand' => 65, 'issued' => 18,
             'description' => 'Galvanized corrugated sheet for roofing and site fencing.'],
            ['name' => 'Expansion Bolt 3/8" x 4" (box of 50)', 'sku' => 'HDW-BT-2003',
             'category' => 'Hardware', 'brand' => 'Bosch', 'supplier' => 'Fastener World Trading',
             'price' => 890.00, 'reorder' => 10, 'on_hand' => 4, 'issued' => 8,
             'description' => 'Wedge anchors for mounting brackets on concrete; below reorder level.'],
            ['name' => 'Butt Hinge 4" (pair)', 'sku' => 'HDW-HG-2004',
             'category' => 'Hardware', 'brand' => $stanley, 'supplier' => 'Hardware Depot Trading',
             'price' => 95.00, 'reorder' => 25, 'on_hand' => 72, 'issued' => 20,
             'description' => 'Door hinges for site carpentry and door installation.'],

            // ---------- Tools ----------
            ['name' => 'Claw Hammer 20oz', 'sku' => 'TLS-HM-3001',
             'category' => 'Tools', 'brand' => $stanley, 'supplier' => 'Hardware Depot Trading',
             'price' => 520.00, 'reorder' => 10, 'on_hand' => 26, 'issued' => 9,
             'description' => 'Forged steel head with grip handle; the crew favourite.'],
            ['name' => 'Cordless Drill 12V Kit', 'sku' => 'TLS-DR-3002',
             'category' => 'Tools', 'brand' => 'Makita', 'supplier' => 'Toolmart Equipment Corp.',
             'price' => 4250.00, 'reorder' => 5, 'on_hand' => 9, 'issued' => 4,
             'description' => 'Battery drill-driver kit for installation and framing crews.'],
            ['name' => 'Angle Grinder 4"', 'sku' => 'TLS-AG-3003',
             'category' => 'Tools', 'brand' => 'DeWalt', 'supplier' => 'Toolmart Equipment Corp.',
             'price' => 2680.00, 'reorder' => 6, 'on_hand' => 0, 'issued' => 7,
             'description' => 'Metal cutting and rebar grinding; sold out, reorder from supplier.'],
            ['name' => 'Steel Tape Measure 5m', 'sku' => 'TLS-TM-3004',
             'category' => 'Tools', 'brand' => $stanley, 'supplier' => 'Hardware Depot Trading',
             'price' => 285.00, 'reorder' => 12, 'on_hand' => 34, 'issued' => 11,
             'description' => 'Locking tape for measuring cuts and take-offs.'],
            ['name' => 'Circular Saw Blade 7-1/4" (40T)', 'sku' => 'TLS-BL-3005',
             'category' => 'Tools', 'brand' => 'Bosch', 'supplier' => 'Toolmart Equipment Corp.',
             'price' => 745.00, 'reorder' => 8, 'on_hand' => 6, 'issued' => 5,
             'description' => 'Carbide-tipped framing blade; only six packs left on the shelf.'],

            // ---------- Electrical ----------
            ['name' => 'THHN Wire 2.0 sq.mm (250m roll)', 'sku' => 'ELC-WR-4001',
             'category' => 'Electrical', 'brand' => 'Omni Power', 'supplier' => 'PhilWood Electrical Supply',
             'price' => 3150.00, 'reorder' => 4, 'on_hand' => 12, 'issued' => 5,
             'description' => 'Copper building wire for rough-in wiring, sold by the roll.'],
            ['name' => 'Extension Cord 5-Outlet 5m', 'sku' => 'ELC-EC-4002',
             'category' => 'Electrical', 'brand' => $firefly, 'supplier' => 'PhilWood Electrical Supply',
             'price' => 465.00, 'reorder' => 10, 'on_hand' => 3, 'issued' => 9,
             'description' => 'Heavy-duty cord for job-site power; low stock flagged.'],
            ['name' => 'LED Bulb 12W (pack of 6)', 'sku' => 'ELC-LED-4003',
             'category' => 'Electrical', 'brand' => 'Philips', 'supplier' => 'PhilWood Electrical Supply',
             'price' => 185.00, 'reorder' => 24, 'on_hand' => 90, 'issued' => 30,
             'description' => 'Energy-saving bulbs for showrooms and site temporary lighting.'],
            ['name' => 'Electrical Tape (pack of 10)', 'sku' => 'ELC-TA-4004',
             'category' => 'Electrical', 'brand' => $firefly, 'supplier' => 'PhilWood Electrical Supply',
             'price' => 75.00, 'reorder' => 20, 'on_hand' => 48, 'issued' => 22,
             'description' => 'PVC insulating tape for wire joints.'],

            // ---------- Plumbing ----------
            ['name' => 'PVC Pipe 1/2" x 3m', 'sku' => 'PLB-PVC-5001',
             'category' => 'Plumbing', 'brand' => $marson, 'supplier' => 'AquaLine Plumbing Depot',
             'price' => 145.00, 'reorder' => 50, 'on_hand' => 210, 'issued' => 65,
             'description' => 'Pressure pipe for residential water lines.'],
            ['name' => 'PVC Elbow 1/2"', 'sku' => 'PLB-EL-5002',
             'category' => 'Plumbing', 'brand' => $marson, 'supplier' => 'AquaLine Plumbing Depot',
             'price' => 18.00, 'reorder' => 60, 'on_hand' => 180, 'issued' => 40,
             'description' => 'Schedule 40 fitting for water line corners.'],
            ['name' => 'Gate Valve 1"', 'sku' => 'PLB-VL-5003',
             'category' => 'Plumbing', 'brand' => $dover, 'supplier' => 'AquaLine Plumbing Depot',
             'price' => 385.00, 'reorder' => 12, 'on_hand' => 0, 'issued' => 16,
             'description' => 'Brass main-line valve; fully sold, purchase order pending.'],
            ['name' => 'Flexible Hose 16"', 'sku' => 'PLB-HT-5004',
             'category' => 'Plumbing', 'brand' => $dover, 'supplier' => 'AquaLine Plumbing Depot',
             'price' => 165.00, 'reorder' => 15, 'on_hand' => 22, 'issued' => 8,
             'description' => 'Stainless connector hose for sink and shower installs.'],

            // ---------- Paint ----------
            ['name' => 'Boysen Latex Paint 4L (White)', 'sku' => 'PNT-LTX-6001',
             'category' => 'Paint', 'brand' => 'Boysen', 'supplier' => 'Colorhouse Paint Supply',
             'price' => 1150.00, 'reorder' => 10, 'on_hand' => 28, 'issued' => 12,
             'description' => 'Interior latex for ceiling and wall finishing, anti-fungal.'],
            ['name' => 'Davies Quick Drip Enamel 1L', 'sku' => 'PNT-EN-6002',
             'category' => 'Paint', 'brand' => 'Davies', 'supplier' => 'Colorhouse Paint Supply',
             'price' => 495.00, 'reorder' => 12, 'on_hand' => 7, 'issued' => 15,
             'description' => 'Gloss enamel for doors, gates and metal surfaces; low stock.'],
            ['name' => 'Paint Brush 3"', 'sku' => 'PNT-BR-6003',
             'category' => 'Paint', 'brand' => 'Davies', 'supplier' => 'Colorhouse Paint Supply',
             'price' => 135.00, 'reorder' => 20, 'on_hand' => 55, 'issued' => 25,
             'description' => 'Flat bristle brush for latex walls and trim.'],
        ];

        foreach ($products as $index => $item) {
            $product = Product::create([
                'name'          => $item['name'],
                'sku'           => $item['sku'],
                'description'   => $item['description'],
                'brand_id'      => $brands[$item['brand']],
                'category_id'   => $categories[$item['category']],
                'quantity'      => 0,
                'reorder_level' => $item['reorder'],
                'unit_price'    => $item['price'],
                'supplier'      => $item['supplier'],
            ]);

            // Receiving document for the opening stock.
            $this->move($product, InventoryTransaction::TYPE_IN,
                $item['on_hand'] + $item['issued'], 'PO-2026-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT), $userId);

            if ($item['issued'] > 0) {
                $this->move($product, InventoryTransaction::TYPE_OUT,
                    $item['issued'], 'SO-' . str_pad((string) ($index + 7), 4, '0', STR_PAD_LEFT), $userId);
            }
        }
    }

    /** Record one movement; the model updates the quantity on hand. */
    private function move(Product $product, string $type, int $quantity, string $reference, ?int $userId): void
    {
        InventoryTransaction::forceCreate([
            'product_id' => $product->id,
            'type'       => $type,
            'quantity'   => $quantity,
            'reference'  => $reference,
            'user_id'    => $userId,
            'created_at' => now()->subDays($type === InventoryTransaction::TYPE_IN ? random_int(3, 6) : random_int(0, 2)),
        ]);
    }
}
