<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Part 2 Step 2 — test account used for the login screenshots.
        // (The same user can be created by hand with artisan tinker; the commands
        // are listed in README.md.)
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'              => 'Supply Centre Admin',
                'password'          => 'password123',   // hashed by the User model cast
                'email_verified_at' => now(),
            ]
        );

        // Brands and categories must exist before the products that reference them.
        $this->call([
            CategorySeeder::class,
            BrandSeeder::class,
            ProductSeeder::class,
        ]);
    }
}