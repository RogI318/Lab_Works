<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Mark one notification as read, then go to the student it is about. */
    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $studentId = $notification->data['student_id'] ?? null;

        if ($studentId && Student::whereKey($studentId)->exists()) {
            return redirect()->route('students.show', $studentId);
        }

        return redirect()->route('dashboard');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}