<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Lab 7 Part 1 Step 2: shared polymorphic audit trail.
 *
 * Any model that uses this trait gets (a) the activities() relation and
 * (b) logActivity(), which writes one activity_logs row for "who did what".
 * Supplier uses it now; PurchaseOrder uses the same trait in Part 2, which is
 * why the PO approval history needs no table of its own.
 */
trait LogsActivity
{
    /** Every recorded event for this record, newest first. */
    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'loggable')->latest();
    }

    /**
     * Called explicitly from the controller (not from a model event) so only
     * real, authorised user actions are recorded — a seeder touching the row
     * must not look like an edit in the audit trail.
     *
     * $userId is for the paths that have no logged-in user at all: the Part 3
     * scheduler runs on a console process, so the order it raises is credited
     * to the administrator the engine acts for.
     */
    public function logActivity(string $action, string $description, array $properties = [], ?int $userId = null): ActivityLog
    {
        return $this->activities()->create([
            'user_id'     => $userId ?? auth()->id(),
            'action'      => $action,
            'description' => $description,
            'properties'  => $properties ?: null,
        ]);
    }
}
