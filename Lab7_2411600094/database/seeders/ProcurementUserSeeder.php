<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Lab 7 Part 2 Step 6: one account per procurement role, so the authorization
 * matrix can be demonstrated by logging in rather than by reading the policy.
 *
 * Idempotent (updateOrCreate on the e-mail) and these are demo accounts with a
 * known throwaway password — never reuse them anywhere real.
 */
class ProcurementUserSeeder extends Seeder
{
    /** name, e-mail, role, is_admin is derived from the role by the User model. */
    private const PEOPLE = [
        ['Procurement Manager', 'manager@example.com',  User::ROLE_MANAGER],
        ['Warehouse Receiver',  'warehouse@example.com', User::ROLE_WAREHOUSE],
        ['Store Staff',         'staff@example.com',     User::ROLE_STAFF],
    ];

    public function run(): void
    {
        foreach (self::PEOPLE as [$name, $email, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name'              => $name,
                    'password'          => 'password123',   // hashed by the cast
                    'email_verified_at' => now(),
                    'role'              => $role,
                ]
            );
        }

        $this->command?->info(sprintf(
            'Procurement accounts: %d ready (manager / warehouse / staff, password password123).',
            count(self::PEOPLE)
        ));
    }
}
