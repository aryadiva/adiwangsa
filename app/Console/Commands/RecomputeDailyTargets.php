<?php

namespace App\Console\Commands;

use App\Services\DeficitCarryForwardService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RecomputeDailyTargets extends Command
{
    protected $signature = 'daily-targets:recompute {--date= : The date to evaluate (defaults to today)}';

    protected $description = 'Recompute daily targets with carried deficit and evaluate target delay warnings.';

    public function handle(): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();

        DeficitCarryForwardService::runNightlyEvaluation($date);

        $this->info('Daily targets recomputed and deficit warnings evaluated for '.$date->toDateString().'.');

        return self::SUCCESS;
    }
}
