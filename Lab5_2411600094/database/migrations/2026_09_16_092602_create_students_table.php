<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique();
            $table->string('name');
            $table->string('program');
            $table->unsignedTinyInteger('year_level');
            $table->unsignedTinyInteger('units')->default(0);
            $table->decimal('gpa', 3, 2)->default(0.00);
            $table->unsignedTinyInteger('attendance')->default(0);
            $table->unsignedTinyInteger('pending_assignments')->default(0);
            $table->string('status')->default('Good Standing');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};