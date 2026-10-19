<?php

namespace App\Observers;

use App\Models\AssignmentTransaction;
use App\Models\Student;
use App\Models\User;
use App\Notifications\HeavyBacklogNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class AssignmentTransactionObserver
{
    /**
     * BEFORE the row is saved: block anything that would make the
     * student's pending count negative (Option A: throw + rollback).
     */
    public function creating(AssignmentTransaction $transaction): void
    {
        $student = Student::lockForUpdate()->findOrFail($transaction->student_id);

        if ($student->pending_assignments + $transaction->quantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => "{$student->name} only has {$student->pending_assignments} pending assignment(s); "
                            . 'the pending count cannot go below zero.',
            ]);
        }
    }

    /**
     * AFTER the row is saved: update the student's pending count and
     * send a notification when the student crosses into Heavy Backlog.
     */
    public function created(AssignmentTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $student = Student::lockForUpdate()->findOrFail($transaction->student_id);

            $before = $student->pending_assignments;
            $after  = $before + $transaction->quantity;

            $student->pending_assignments = $after;
            $student->save();

            // Only notify when the limit is CROSSED (prevents duplicate alerts
            // while the student is already in Heavy Backlog).
            if ($before < Student::BACKLOG_LIMIT && $after >= Student::BACKLOG_LIMIT) {
                Notification::send(User::all(), new HeavyBacklogNotification($student));
            }
        });
    }
}