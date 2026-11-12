<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Confirm payments without waiting for Midtrans notifications (which cannot reach localhost).
// The lock expires after 5 minutes, so a run that was killed never blocks the next ones for long.
Schedule::command('payments:check-pending')->everyMinute()->withoutOverlapping(5);
