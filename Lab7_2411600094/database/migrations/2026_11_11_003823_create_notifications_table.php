<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab 6 Part 6 Step 2 — Laravel's standard notifications table
     * (needed by the Part 5 bell, so it lands before the UI goes live).
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');          // notifiable_type + notifiable_id
            $table->text('data');                  // JSON payload
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // "my unread alerts, newest first" is the hot query:
            $table->index(['notifiable_type', 'notifiable_id', 'read_at', 'created_at'],
                          'notifications_inbox_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
