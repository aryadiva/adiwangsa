<?php

use App\Enums\PayrollRunStatus;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Site;
use App\Models\Worker;
use App\Models\WorkerAttendance;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * Seed a worker with per-day attendance for the window [today-14, today-1].
 *
 * @return array{0: Worker, 1: Site}
 */
function workerWithAttendance(float $dailyRate, array $dayHours, float $overtimePerDay = 0.0): array
{
    $worker = Worker::factory()->create(['daily_rate' => $dailyRate]);
    $site = Site::factory()->create();

    foreach ($dayHours as $offset => $hours) {
        WorkerAttendance::factory()->create([
            'worker_id' => $worker->id,
            'site_id' => $site->id,
            'attendance_date' => Carbon::today()->subDays(14 - $offset)->toDateString(),
            'hours_worked' => $hours,
            'overtime_hours' => $overtimePerDay,
            'captured_at' => now(),
        ]);
    }

    return [$worker, $site];
}

it('derives regular and overtime pay from attendance across a 14-day window', function () {
    // 1,600,000 / 8 = 200,000 hourly.
    [$worker] = workerWithAttendance(1600000, [8, 8, 8, 8, 8, 8, 8, 8, 8, 8], overtimePerDay: 6);

    $run = PayrollService::runForPeriod(Carbon::today()->subDays(14), Carbon::today()->subDay());

    $item = $run->items()->where('worker_id', $worker->id)->first();

    expect($item->regular_hours_total)->toBe('80.00')
        ->and($item->overtime_hours_total)->toBe('60.00')
        ->and($item->regular_pay)->toBe('16000000.00')
        ->and($item->overtime_pay)->toBe('12000000.00')
        ->and($item->total_pay)->toBe('28000000.00')
        ->and($run->status)->toBe(PayrollRunStatus::Draft);
});

it('pro-rates pay for partial-day attendance', function () {
    // 800,000 / 8 = 100,000 hourly; one 6-hour day = 600,000.
    [$worker] = workerWithAttendance(800000, [6]);

    $run = PayrollService::runForPeriod(Carbon::today()->subDays(14), Carbon::today()->subDay());

    $item = $run->items()->where('worker_id', $worker->id)->first();

    expect($item->regular_hours_total)->toBe('6.00')
        ->and($item->regular_pay)->toBe('600000.00')
        ->and($item->total_pay)->toBe('600000.00');
});

it('honors an env-configurable standard workday length', function () {
    config()->set('payroll.standard_workday_hours', 10);

    // 1,000,000 / 10 = 100,000 hourly (would be 125,000 at the 8h default).
    [$worker] = workerWithAttendance(1000000, [10]);

    $run = PayrollService::runForPeriod(Carbon::today()->subDays(14), Carbon::today()->subDay());

    $item = $run->items()->where('worker_id', $worker->id)->first();

    expect($item->regular_pay)->toBe('1000000.00');
});

it('excludes workers without attendance in the window', function () {
    Worker::factory()->create(['daily_rate' => 1500000]);

    $run = PayrollService::runForPeriod(Carbon::today()->subDays(14), Carbon::today()->subDay());

    expect($run->items()->count())->toBe(0);
});

it('still pays a worker who was soft-deleted after the period', function () {
    [$worker] = workerWithAttendance(1600000, [8]);

    $worker->delete();

    $run = PayrollService::runForPeriod(Carbon::today()->subDays(14), Carbon::today()->subDay());

    expect($run->items()->where('worker_id', $worker->id)->first()->total_pay)->toBe('1600000.00');
});

it('is idempotent per period — a re-run returns the existing run', function () {
    workerWithAttendance(1600000, [8, 8]);

    $first = PayrollService::runForPeriod(Carbon::today()->subDays(14), Carbon::today()->subDay());
    $second = PayrollService::runForPeriod(Carbon::today()->subDays(14), Carbon::today()->subDay());

    expect($second->id)->toBe($first->id)
        ->and(PayrollRun::count())->toBe(1)
        ->and(PayrollItem::count())->toBe(1);
});

it('generates nothing when today is not a cycle boundary', function () {
    config()->set('payroll.cycle_anchor_date', Carbon::today()->subDays(3)->toDateString());

    $run = PayrollService::runCycleDue(Carbon::today());

    expect($run)->toBeNull()
        ->and(PayrollRun::count())->toBe(0);
});

it('generates the previous cycle when today is a cycle boundary', function () {
    config()->set('payroll.cycle_anchor_date', Carbon::today()->subDays(14)->toDateString());
    [$worker] = workerWithAttendance(1600000, [8, 8, 8]);

    $run = PayrollService::runCycleDue(Carbon::today());

    expect($run)->not->toBeNull()
        ->and($run->period_start->toDateString())->toBe(Carbon::today()->subDays(14)->toDateString())
        ->and($run->period_end->toDateString())->toBe(Carbon::today()->subDay()->toDateString())
        ->and($run->generated_by_user_id)->toBeNull()
        ->and($run->items()->where('worker_id', $worker->id)->exists())->toBeTrue();
});

it('skips the due cycle gracefully when the run already exists', function () {
    config()->set('payroll.cycle_anchor_date', Carbon::today()->subDays(14)->toDateString());

    PayrollService::runForPeriod(Carbon::today()->subDays(14), Carbon::today()->subDay());

    $this->artisan('payroll:generate')->expectsOutputToContain('already exists')->assertExitCode(0);
    expect(PayrollRun::count())->toBe(1);
});

it('no-ops via the command when no cycle is due', function () {
    config()->set('payroll.cycle_anchor_date', Carbon::today()->subDays(3)->toDateString());

    $this->artisan('payroll:generate')
        ->expectsOutputToContain('No payroll cycle due')
        ->assertExitCode(0);

    expect(PayrollRun::count())->toBe(0);
});

it('generates the due cycle via the command', function () {
    config()->set('payroll.cycle_anchor_date', Carbon::today()->subDays(14)->toDateString());
    workerWithAttendance(1600000, [8]);

    $this->artisan('payroll:generate')->assertExitCode(0);

    expect(PayrollRun::count())->toBe(1)
        ->and(PayrollRun::first()->status)->toBe(PayrollRunStatus::Draft);
});

it('backfills an explicit period via the command', function () {
    $worker = Worker::factory()->create(['daily_rate' => 1600000]);
    $site = Site::factory()->create();

    WorkerAttendance::factory()->create([
        'worker_id' => $worker->id,
        'site_id' => $site->id,
        'attendance_date' => Carbon::today()->subDays(20)->toDateString(),
        'hours_worked' => 8,
        'overtime_hours' => 2,
    ]);

    $start = Carbon::today()->subDays(30)->toDateString();
    $end = Carbon::today()->subDays(17)->toDateString();

    $this->artisan("payroll:generate --start={$start} --end={$end}")->assertExitCode(0);

    $run = PayrollRun::query()->sole();

    expect($run->period_start->toDateString())->toBe($start)
        ->and($run->items()->sole()->overtime_hours_total)->toBe('2.00');
});

it('rejects a command invocation with only one boundary', function () {
    $this->artisan('payroll:generate --start=2026-01-01')->assertExitCode(1);
});
