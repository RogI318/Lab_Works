<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\User;
use App\Mail\AcademicRiskAlert;
use Illuminate\Support\Facades\Mail;

class CheckAcademicRisk extends Command
{
    protected $signature = 'students:check-academic-risk';

    protected $description = 'Check students with heavy assignment backlogs and notify administrators';

    public function handle(): int
    {
        $this->info('Starting academic-risk check...');

        $students = Student::heavyBacklog()->get();

        if ($students->isEmpty()) {
            $this->info('No students with heavy backlogs were found.');

            return self::SUCCESS;
        }

        $this->warn($students->count() . ' student(s) with heavy backlogs found.');

        $admins = User::where('is_admin', true)
            ->whereNotNull('email')
            ->get();

        if ($admins->isEmpty()) {
            $this->warn('No administrator email addresses were found.');

            return self::SUCCESS;
        }

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(
                new AcademicRiskAlert($students)
            );

            $this->line('Notification sent to: ' . $admin->email);
        }

        $this->info('Academic-risk check completed successfully.');

        return self::SUCCESS;
    }
}
