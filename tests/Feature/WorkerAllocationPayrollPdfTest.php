<?php

use App\DTOs\ReportDataDTO;
use App\Enums\DocumentType;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Worker;
use App\Services\PdfReportService;
use Barryvdh\DomPDF\PDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('appends the payroll pay breakdown when a payroll run is provided', function () {
    $worker = Worker::factory()->create([
        'full_name' => 'Budi Santoso',
        'trade_skill' => 'Mason',
        'daily_rate' => 1600000,
    ]);

    $run = PayrollRun::factory()->create([
        'period_start' => '2026-08-17',
        'period_end' => '2026-08-30',
    ]);

    PayrollItem::create([
        'payroll_run_id' => $run->id,
        'worker_id' => $worker->id,
        'regular_hours_total' => 80,
        'overtime_hours_total' => 12,
        'regular_pay' => 16000000,
        'overtime_pay' => 2400000,
        'total_pay' => 18400000,
    ]);

    $dto = ReportDataDTO::forWorkerAllocation(
        collect([]),
        Carbon::parse('2026-08-17'),
        Carbon::parse('2026-08-30'),
        'en',
        $run,
    );

    expect($dto->type)->toBe(DocumentType::WorkerAllocationPayroll)
        ->and($dto->payrollItems)->toHaveCount(1)
        ->and($dto->payrollItems[0]['name'])->toBe('Budi Santoso')
        ->and($dto->payrollItems[0]['regular_hours'])->toBe('80.00')
        ->and($dto->payrollItems[0]['overtime_hours'])->toBe('12.00')
        ->and($dto->payrollItems[0]['total_pay'])->toBe('18,400,000.00')
        ->and($dto->payrollTotal)->toBe('18,400,000.00');
});

it('omits the payroll section without a payroll run', function () {
    $dto = ReportDataDTO::forWorkerAllocation(
        collect([]),
        Carbon::parse('2026-08-17'),
        Carbon::parse('2026-08-30'),
    );

    expect($dto->payrollItems)->toBe([])
        ->and($dto->payrollTotal)->toBeNull();
});

it('passes the payroll-enriched DTO to the worker-allocation-payroll Blade view', function () {
    $worker = Worker::factory()->create([
        'full_name' => 'Budi Santoso',
        'daily_rate' => 1600000,
    ]);

    $run = PayrollRun::factory()->create();
    PayrollItem::create([
        'payroll_run_id' => $run->id,
        'worker_id' => $worker->id,
        'regular_hours_total' => 80,
        'overtime_hours_total' => 12,
        'regular_pay' => 16000000,
        'overtime_pay' => 2400000,
        'total_pay' => 18400000,
    ]);

    $dto = ReportDataDTO::forWorkerAllocation(
        collect([]),
        Carbon::today()->subDays(14),
        Carbon::today()->subDay(),
        'en',
        $run,
    );

    $this->mock(PDF::class, function ($mock) use ($dto) {
        $mock->shouldReceive('loadView')
            ->once()
            ->with('pdf.worker-allocation-payroll', Mockery::on(
                fn (array $data): bool => ($data['dto'] ?? null) === $dto
                    && $data['dto']->payrollItems[0]['name'] === 'Budi Santoso'
                    && $data['dto']->payrollTotal === '18,400,000.00'
            ))
            ->andReturnSelf();
        $mock->shouldReceive('output')->andReturn('payroll-pdf-bytes');
    });

    $bytes = $this->app->make(PdfReportService::class)->render($dto);

    expect($bytes)->toBe('payroll-pdf-bytes');
});
