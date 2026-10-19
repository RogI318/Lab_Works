<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo account shown on the login page (also the administrator)
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => 'password123']
        );
        $admin->forceFill(['is_admin' => true])->save();

        $this->call([
            CourseSeeder::class,
            StudentSeeder::class,
            ActivitySeeder::class,
            AssignmentTransactionSeeder::class,
        ]);
    }
}