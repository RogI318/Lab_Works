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
            ['email' => 'reyzlyn588@gmail.com'],
            [
                'name'              => 'RogieMart Admin',
                'password'          => 'password123',   // hashed by the User model cast
                'email_verified_at' => now(),
                'is_admin'          => true,
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