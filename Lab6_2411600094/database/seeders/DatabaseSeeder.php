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

        // Normal test user (Lab 5 features only)
        $test = User::updateOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'John Doe', 'password' => 'password123']
        );
        $test->forceFill(['is_admin' => false])->save();

        $this->call([
            CourseSeeder::class,
            StudentSeeder::class,
            ActivitySeeder::class,
            AssignmentTransactionSeeder::class,
        ]);
    }
}