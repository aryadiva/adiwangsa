<?php

namespace App\Console\Commands;

use App\Services\DelayCascadeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DetectSubJobDelays extends Command
{
    protected $signature = 'sub-job-delays:detect {--date= : The date to evaluate (defaults to today)}';

    protected $description = 'Detect sub-job threshold breaches and create red delay events with downstream date cascades.';

    public function handle(): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();

        $created = DelayCascadeService::runDetection($date);

        $this->info(count($created).' delay event(s) created for '.$date->toDateString().'.');

        return self::SUCCESS;
    }
}
