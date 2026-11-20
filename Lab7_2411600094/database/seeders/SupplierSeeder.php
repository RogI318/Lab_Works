<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Metro Lumber Supply',
                'contact_person' => 'Sales Representative',
                'email' => 'metro@example.test',
                'phone' => '0917-555-0101',
                'lead_time_days' => 3,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Northside Lumber Yard',
                'contact_person' => 'Sales Representative',
                'email' => 'northside@example.test',
                'phone' => '0917-555-0102',
                'lead_time_days' => 4,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Hardware Depot Trading',
                'contact_person' => 'Sales Representative',
                'email' => 'hardware@example.test',
                'phone' => '0917-555-0103',
                'lead_time_days' => 2,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Fastener World Trading',
                'contact_person' => 'Sales Representative',
                'email' => 'fasteners@example.test',
                'phone' => '0917-555-0104',
                'lead_time_days' => 3,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Toolmart Equipment Corp.',
                'contact_person' => 'Sales Representative',
                'email' => 'toolmart@example.test',
                'phone' => '0917-555-0105',
                'lead_time_days' => 5,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'PhilWood Electrical Supply',
                'contact_person' => 'Sales Representative',
                'email' => 'philwood@example.test',
                'phone' => '0917-555-0106',
                'lead_time_days' => 3,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'AquaLine Plumbing Depot',
                'contact_person' => 'Sales Representative',
                'email' => 'aqualine@example.test',
                'phone' => '0917-555-0107',
                'lead_time_days' => 4,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Colorhouse Paint Supply',
                'contact_person' => 'Sales Representative',
                'email' => 'colorhouse@example.test',
                'phone' => '0917-555-0108',
                'lead_time_days' => 2,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $attributes) {
            $supplier = Supplier::firstOrCreate(
                ['name' => $attributes['name']],
                $attributes
            );

            $updates = [];
            if (in_array($supplier->contact_person, [null, 'Demo Supplier Contact'], true)) {
                $updates['contact_person'] = 'Sales Representative';
            }
            if ($supplier->address === 'Demo supplier address') {
                $updates['address'] = null;
            }
            if ($supplier->email === null || str_ends_with($supplier->email, '@example.test')) {
                $updates['email'] = $attributes['email'];
            }
            if ($supplier->phone === null || preg_match('/^09\d{2}-555-01\d{2}$/', $supplier->phone)) {
                $updates['phone'] = $attributes['phone'];
            }
            if ($updates !== []) {
                $supplier->update($updates);
            }

            Product::where('supplier', $supplier->name)
                ->whereNull('supplier_id')
                ->update(['supplier_id' => $supplier->id]);
        }
    }
}
