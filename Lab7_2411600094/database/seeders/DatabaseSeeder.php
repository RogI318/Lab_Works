<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Part 2 Step 2 — test account used for the login screenshots.
        // This account is the administrator who can manage other users.
        User::updateOrCreate(
            ['email' => 'rogielynamisola27@gmail.com'],
            [
                'name'              => 'RogieMart Admin',
                'password'          => 'password123',   // hashed by the User model cast
                'email_verified_at' => now(),
                'is_admin'          => true,
            ]
        );

        // Brands and categories must exist before the products that reference them.
        // Lab 7 Part 1: SupplierSeeder sits between the two — the suppliers it
        // creates have to exist before ProductSeeder assigns a primary supplier,
        // and it also backfills supplier_id for products that are already in the
        // table from Lab 5/6.
        $this->call([
            CategorySeeder::class,
            BrandSeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
            // Lab 7 Part 2: the role accounts first, because the demo purchase
            // orders attribute approval and receipt to those users.
            ProcurementUserSeeder::class,
            PurchaseOrderSeeder::class,
        ]);
    }
}