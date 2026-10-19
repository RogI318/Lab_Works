<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo account shown on the login page
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => 'password123']   // hashed by the User model cast
        );

        $this->call([
            CourseSeeder::class,
            StudentSeeder::class,
            ActivitySeeder::class,
        ]);
    }
}