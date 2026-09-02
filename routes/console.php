<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly deficit carry-forward target recomputation.
// MUST run before shift-report evaluation (order matters — AGENTS.md gotcha).
Schedule::command('daily-targets:recompute')->dailyAt('00:30');

// Sub-job delay cascade detection. Runs after daily-targets:recompute so
// evaluation sees fresh targets (same AGENTS.md ordering gotcha).
Schedule::command('sub-job-delays:detect')->dailyAt('00:45');

// Bi-weekly payroll generation. The command self-gates on the 14-day cycle
// boundary derived from config('payroll.cycle_anchor_date') and no-ops on
// any other day. Scheduled after the nightly target/delay jobs so it never
// shares their 00:30/00:45 window.
Schedule::command('payroll:generate')->dailyAt('01:15');
