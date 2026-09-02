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
