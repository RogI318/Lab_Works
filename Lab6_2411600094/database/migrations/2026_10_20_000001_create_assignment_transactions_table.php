<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_transactions', function (Blueprint $table) {
            $table->id();
            // Deleting a student deletes their transactions (cascade);
            // deleting a user keeps the audit rows (user_id becomes null).
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['assigned', 'submitted', 'adjustment']);
            $table->integer('quantity');                    // signed: + backlog up, - backlog down
            $table->string('reference_number', 50)->nullable();
            $table->string('reason')->nullable();           // used by adjustments
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_transactions');
    }
};