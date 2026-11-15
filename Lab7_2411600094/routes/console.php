<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Lab 7 Part 3 Step 5: the daily reorder sweep.
 *
 * Laravel 12 has no app/Console/Kernel.php — scheduling belongs here, and
 * `php artisan schedule:list` is the proof it took. withoutOverlapping() keeps
 * two sweeps off the same table if the machine is slow, and the output file is
 * what you read after a night nobody was watching.
 */
Schedule::command('inventory:check-reorder-points')
    ->dailyAt(config('procurement.reorder_check_time'))
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/reorder-check.log'))
    ->onFailure(function () {
        Log::error(sprintf(
            'Scheduled reorder check (%s) exited with an error — see storage/logs/reorder-check.log.',
            config('procurement.reorder_check_time')
        ));
    });
