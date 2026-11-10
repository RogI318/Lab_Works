<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The Lab 4 / student-portal tables are replaced by the inventory schema. */
    private array $legacy = [
        'academic_interventions',
        'assignment_transactions',
        'activities',
        'courses',
        'students',
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ($this->legacy as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // The legacy student-portal schema is not restored.
    }
};
