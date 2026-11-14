<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lab 7 Part 1 Step 2: the supplier master file.
 *
 * Idempotent (updateOrCreate keyed on the unique email), so it can be re-run
 * as often as you like:
 *   php artisan db:seed --class=SupplierSeeder
 *
 * It does three jobs:
 *   1. create/update the eight vendors RogieMart already buys from — the same
 *      names that sit in the legacy free-text products.supplier column;
 *   2. backfill products.supplier_id from that text, so Lab 6's 23 products
 *      become linked without being recreated;
 *   3. write the product_supplier price agreements (the primary vendor marked
 *      preferred, plus a couple of alternates for the items that matter).
 */
class SupplierSeeder extends Seeder
{
    /** unit_cost = selling price x this, i.e. the trade margin we keep. */
    private const COST_RATIO = 0.70;

    /**
     * How each category is actually packed and ordered, used to make the
     * pivot's package_size / minimum_order_quantity believable.
     */
    private const PACKING = [
        'Lumber'     => ['box' => 10, 'moq' => 20],
        'Hardware'   => ['box' => 50, 'moq' => 50],
        'Tools'      => ['box' => 5,  'moq' => 5],
        'Electrical' => ['box' => 10, 'moq' => 10],
        'Plumbing'   => ['box' => 100, 'moq' => 100],
        'Paint'      => ['box' => 6,  'moq' => 12],
        'default'    => ['box' => 10, 'moq' => 10],
    ];

    /**
     * The eight vendors. Terms, lead times and order floors are spread across
     * the realistic range so the Part 3 reorder engine has something to choose
     * between (a 3-day lumber yard vs a 14-day imported tool vendor).
     */
    private const SUPPLIERS = [
        [
            'name' => 'Metro Lumber Supply', 'contact_person' => 'Rogelio Mendoza',
            'email' => 'sales@metrolumbersupply.ph', 'phone' => '(02) 8724-1188',
            'address' => 'Blk 12 Lot 4, Commonwealth Avenue, Brgy. Old Balara, Quezon City 1105',
            'payment_terms' => 'Net 30', 'lead_time_days' => 5, 'minimum_order_amount' => 15000.00,
            'notes' => 'Coco lumber, marine plywood and GI sheets. Deliveries Tuesday and Friday, 8AM-4PM.',
        ],
        [
            'name' => 'Northside Lumber Yard', 'contact_person' => 'Maricel Bautista',
            'email' => 'orders@northsidelumber.ph', 'phone' => '0917-555-2841',
            'address' => 'No. 7 Quirino Highway, San Bartolome, Novaliches, Quezon City 1116',
            'payment_terms' => 'COD', 'lead_time_days' => 3, 'minimum_order_amount' => 5000.00,
            'notes' => 'Same-day pick-up if ordered before 10AM. Cash on delivery only.',
        ],
        [
            'name' => 'Hardware Depot Trading', 'contact_person' => 'Ernesto Villanueva',
            'email' => 'info@hardwaredepottrading.com.ph', 'phone' => '(02) 8633-9021',
            'address' => '325 Rizal Avenue, Santa Cruz, Manila 1003',
            'payment_terms' => 'Net 30', 'lead_time_days' => 7, 'minimum_order_amount' => 10000.00,
            'notes' => 'Nails, hinges and general carpentry hardware. Ask for the volume price list.',
        ],
        [
            'name' => 'Fastener World Trading', 'contact_person' => 'Liza Catapang',
            'email' => 'fastenerworld@yahoo.com.ph', 'phone' => '0928-441-7362',
            'address' => 'Unit 4, GV Avenue Building, Bonifacio Street, Mandaluyong City 1550',
            'payment_terms' => 'Net 15', 'lead_time_days' => 4, 'minimum_order_amount' => 7500.00,
            'notes' => 'Expansion bolts, anchors and threaded rods. Stock is by box of 50.',
        ],
        [
            'name' => 'Toolmart Equipment Corp.', 'contact_person' => 'Danilo Reyes',
            'email' => 'procurement@toolmart.ph', 'phone' => '+63 2 8893 4471',
            'address' => 'Km 18.5 MacArthur Highway, Guiguinto, Bulacan 3015',
            'payment_terms' => 'Net 45', 'lead_time_days' => 14, 'minimum_order_amount' => 25000.00,
            'notes' => 'Power tools and blades. Imported items may slip by a week — order early.',
        ],
        [
            'name' => 'PhilWood Electrical Supply', 'contact_person' => 'Arnel Dizon',
            'email' => 'sales@philwoodelectrical.ph', 'phone' => '0995-772-1043',
            'address' => '118 España Extension, Brgy. Sto. Niño, Sampaloc, Manila 1008',
            'payment_terms' => 'Net 30', 'lead_time_days' => 6, 'minimum_order_amount' => 8000.00,
            'notes' => 'Wires, bulbs and fittings. Certified copper only — no aluminum substitutes.',
        ],
        [
            'name' => 'AquaLine Plumbing Depot', 'contact_person' => 'Rowena Aquino',
            'email' => 'aqualine.plumbing@gmail.com', 'phone' => '(02) 8476-5510',
            'address' => 'Nagtahan Corner, San Lazaro, Sta. Mesa, Manila 1016',
            'payment_terms' => 'Net 60', 'lead_time_days' => 8, 'minimum_order_amount' => 12000.00,
            'notes' => 'PVC pipes, fittings and valves. Schedule 40 stock only, trucks limited to 5 metres.',
        ],
        [
            'name' => 'Colorhouse Paint Supply', 'contact_person' => 'Vilma Cruz',
            'email' => 'orders@colorhousepaint.ph', 'phone' => '0906-318-7745',
            'address' => '24 Aguinaldo Highway, Bacoor City, Cavite 4107',
            'payment_terms' => 'Net 30', 'lead_time_days' => 9, 'minimum_order_amount' => 9000.00,
            'notes' => 'Boysen and Davies lines. Tinted cans cannot be returned.',
        ],
    ];

    /**
     * Alternate vendors for the items whose supply must never depend on one
     * company alone. Used by Part 3 when the primary supplier is too slow.
     */
    private const ALTERNATES = [
        'LMB-COC-1001' => ['Northside Lumber Yard', 'Hardware Depot Trading'],
        'HDW-NL-2001'  => ['Fastener World Trading'],
        'ELC-LED-4003' => ['Hardware Depot Trading'],
        'PLB-PVC-5001' => ['Hardware Depot Trading', 'Metro Lumber Supply'],
    ];

    public function run(): void
    {
        $suppliers = $this->seedSuppliers();

        $this->linkPrimarySupplier($suppliers);
        $this->seedPriceAgreements($suppliers);
    }

    /** Step 1: the supplier rows themselves. */
    private function seedSuppliers(): array
    {
        $byName = [];

        foreach (self::SUPPLIERS as $supplier) {
            $model = Supplier::updateOrCreate(
                ['email' => $supplier['email']],
                array_diff_key($supplier, ['email' => true])
            );

            $byName[$model->name] = $model;
        }

        $this->command?->info('Suppliers: ' . count($byName) . ' master records up to date.');

        return $byName;
    }

    /**
     * Step 2: give every existing product a supplier_id from its old free-text
     * `supplier` value, so Lab 6's data becomes relational in place.
     */
    private function linkPrimarySupplier(array $suppliers): void
    {
        if (! Schema::hasColumn('products', 'supplier_id')) {
            $this->command?->warn('products.supplier_id missing — run php artisan migrate first.');

            return;
        }

        $linked = 0;

        foreach ($suppliers as $name => $supplier) {
            $linked += Product::where('supplier', $name)->update(['supplier_id' => $supplier->id]);
        }

        $this->command?->info("Products linked to a primary supplier: {$linked}.");
    }

    /**
     * Step 3: one preferred price agreement per product with its primary
     * supplier, plus the alternates declared in ALTERNATES.
     */
    private function seedPriceAgreements(array $suppliers): void
    {
        if (! Schema::hasTable('product_supplier')) {
            $this->command?->warn('product_supplier table missing — run php artisan migrate first.');

            return;
        }

        $rows = 0;

        Product::with('category')->whereNotNull('supplier_id')->each(
            function (Product $product) use ($suppliers, &$rows) {
                $supplier = $suppliers[$product->supplier_name] ?? null;

                if ($supplier) {
                    $this->agreement($product, $supplier, true, self::COST_RATIO);
                    $rows++;
                }

                foreach (self::ALTERNATES[$product->sku] ?? [] as $alternate) {
                    if (isset($suppliers[$alternate]) && $alternate !== $product->supplier_name) {
                        // A secondary vendor is quoted slightly higher — that is
                        // why it is not the preferred one.
                        $this->agreement($product, $suppliers[$alternate], false, self::COST_RATIO + 0.05);
                        $rows++;
                    }
                }
            }
        );

        $this->command?->info("Price agreements written to product_supplier: {$rows}.");
    }

    /** Upsert one product/supplier pair using the pivot's unique key. */
    private function agreement(Product $product, Supplier $supplier, bool $preferred, float $costRatio): void
    {
        $packing = self::PACKING[$product->category?->name] ?? self::PACKING['default'];

        // The vendor's own part number: their initials + our SKU.
        $prefix = collect(explode(' ', $supplier->name))
            ->reject(fn (string $word) => $word === '' || $word === 'of')
            ->map(fn (string $word) => mb_strtoupper(mb_substr(trim($word), 0, 1)))
            ->take(3)
            ->implode('');

        $key = ['product_id' => $product->id, 'supplier_id' => $supplier->id];

        $terms = [
            'supplier_sku'           => $prefix . '-' . $product->sku,
            'unit_cost'              => round((float) $product->unit_price * $costRatio, 2),
            'minimum_order_quantity' => $packing['moq'],
            'package_size'           => $packing['box'],
            'is_preferred'           => $preferred,
        ];

        // updateOrInsert would rewrite created_at on every re-run.
        $now = now();
        $stamp = DB::table('product_supplier')->where($key)->exists()
            ? ['updated_at' => $now]
            : ['created_at' => $now, 'updated_at' => $now];

        DB::table('product_supplier')->updateOrInsert($key, $terms + $stamp);
    }
}
