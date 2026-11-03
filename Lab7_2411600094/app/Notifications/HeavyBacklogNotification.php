<?php

namespace App\Notifications;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HeavyBacklogNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Student $student)
    {
        $this->afterCommit();   // dispatch only after the DB transaction commits
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Heavy backlog: ' . $this->student->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("{$this->student->name} ({$this->student->student_id}) now has "
                 . "{$this->student->pending_assignments} pending assignments "
                 . '(limit: ' . Student::BACKLOG_LIMIT . ').')
            ->action('View Student', route('students.show', $this->student))
            ->line('Suggested action: review the student\'s workload and arrange an advisory meeting.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'student_id'   => $this->student->id,
            'student_code' => $this->student->student_id,
            'name'         => $this->student->name,
            'pending'      => $this->student->pending_assignments,
            'limit'        => Student::BACKLOG_LIMIT,
            'message'      => "{$this->student->name} has {$this->student->pending_assignments} pending "
                            . 'assignments (heavy backlog).',
        ];
    }
}