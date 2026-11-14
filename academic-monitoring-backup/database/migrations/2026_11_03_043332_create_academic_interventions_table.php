
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_interventions', function (Blueprint $table) {
            $table->id();

            // Student receiving academic support
            $table->foreignId('student_id')
                ->constrained()
                ->restrictOnDelete();

            // Admin who created the intervention
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Admin who approved or rejected it
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('reference_number')->unique();

            $table->string('intervention_type')->default('Academic Support');

            $table->enum('priority', [
                'Low',
                'Medium',
                'High',
                'Critical'
            ])->default('Medium');

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'in_progress',
                'completed'
            ])->default('pending');

            $table->unsignedInteger('pending_assignments')->default(0);

            $table->text('description')->nullable();

            $table->text('action_plan')->nullable();

            $table->text('rejection_reason')->nullable();

            $table->timestamp('approved_at')->nullable();

            $table->timestamp('scheduled_at')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Soft delete support
            $table->softDeletes();

            // Query optimization
            $table->index(['student_id', 'status']);
            $table->index(['priority', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_interventions');
    }
};
