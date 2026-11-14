<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lab 7 Part 1 Step 2 (c): the polymorphic activity log.
 *
 * One table records "who did what to which record" for every model that uses
 * the LogsActivity trait (Supplier now, PurchaseOrder in Part 2). `loggable`
 * is the morph pair, so the PO approval history, the supplier audit trail and
 * any later module share a single query instead of one history table each.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // The morphs pair: loggable_type = model class, loggable_id = row.
            $table->morphs('loggable');

            // Nullable: a scheduled command can log activity with no author.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('action');
            $table->text('description');

            // Snapshot of the changed values ("approved_by" => ..., old/new qty).
            $table->json('properties')->nullable();

            $table->timestamps();

            // "Show me everything that happened to this supplier", oldest first.
            $table->index(['loggable_type', 'loggable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
