<?php

namespace App\Console\Commands;

use App\Services\PayrollService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GeneratePayrollRuns extends Command
{
    protected $signature = 'payroll:generate
        {--start= : Explicit period start (YYYY-MM-DD) — backfill mode}
        {--end= : Explicit period end (YYYY-MM-DD) — backfill mode}';

    protected $description = 'Generate the bi-weekly payroll run for the due 14-day cycle (or an explicit period).';

    public function handle(): int
    {
        $start = $this->option('start');
        $end = $this->option('end');

        if ($start !== null xor $end !== null) {
            $this->error('Both --start and --end must be provided together.');

            return self::FAILURE;
        }

        if ($start !== null) {
            $run = PayrollService::runForPeriod(Carbon::parse($start), Carbon::parse($end));
        } else {
            $run = PayrollService::runCycleDue(Carbon::today());

            if ($run === null) {
                $this->info('No payroll cycle due today; nothing generated.');

                return self::SUCCESS;
            }
        }

        $verb = $run->wasRecentlyCreated ? 'generated' : 'already exists';

        $this->info("Payroll run for {$run->period_start->toDateString()} → {$run->period_end->toDateString()} {$verb} with {$run->items()->count()} item(s).");

        return self::SUCCESS;
    }
}
