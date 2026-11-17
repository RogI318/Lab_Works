<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Email verification is now required to log in to the system.
     * Mark the accounts that already exist as verified so nobody gets locked out.
     * (New registrations after this still have to verify through the emailed link.)
     */
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Nothing to undo.
    }
};