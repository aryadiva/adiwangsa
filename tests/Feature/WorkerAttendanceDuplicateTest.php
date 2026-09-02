<?php

use App\Models\Site;
use App\Models\Worker;
use App\Models\WorkerAttendance;
use App\Services\AttendanceService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('records attendance and stamps the recorder', function () {
    $worker = Worker::factory()->create();
    $site = Site::factory()->create();

    $attendance = AttendanceService::record([
        'worker_id' => $worker->id,
        'site_id' => $site->id,
        'attendance_date' => '2026-09-01',
        'hours_worked' => 8,
        'overtime_hours' => 2,
        'captured_at' => now(),
        'meta_data' => [],
    ], adminUser());

    expect($attendance->recorded_by_user_id)->not->toBeNull()
        ->and($attendance->hours_worked)->toBe('8.00');
});

it('rejects a second attendance record for the same worker and date with a friendly error', function () {
    $worker = Worker::factory()->create();
    $site = Site::factory()->create();
    $date = '2026-09-01';

    AttendanceService::record([
        'worker_id' => $worker->id,
        'site_id' => $site->id,
        'attendance_date' => $date,
    ]);

    try {
        AttendanceService::record([
            'worker_id' => $worker->id,
            'site_id' => $site->id,
            'attendance_date' => $date,
        ]);

        $this->fail('Expected duplicate attendance to be rejected.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['attendance_date'][0])
            ->toContain('already been recorded');
    }
});

it('enforces the partial unique index on (worker_id, attendance_date)', function () {
    $worker = Worker::factory()->create();
    $site = Site::factory()->create();
    $date = Carbon::today()->toDateString();

    WorkerAttendance::factory()->create([
        'worker_id' => $worker->id,
        'site_id' => $site->id,
        'attendance_date' => $date,
    ]);

    expect(fn () => WorkerAttendance::query()->create([
        'worker_id' => $worker->id,
        'site_id' => $site->id,
        'attendance_date' => $date,
        'hours_worked' => 8,
        'overtime_hours' => 0,
        'meta_data' => [],
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('allows re-recording after the previous record is soft-deleted', function () {
    $worker = Worker::factory()->create();
    $site = Site::factory()->create();
    $date = Carbon::today()->toDateString();

    $first = WorkerAttendance::factory()->create([
        'worker_id' => $worker->id,
        'site_id' => $site->id,
        'attendance_date' => $date,
    ]);

    $first->delete();

    $second = AttendanceService::record([
        'worker_id' => $worker->id,
        'site_id' => $site->id,
        'attendance_date' => $date,
    ]);

    expect($second->exists)->toBeTrue()
        ->and(WorkerAttendance::withTrashed()->count())->toBe(2);
});

it('allows different workers on the same date', function () {
    $site = Site::factory()->create();
    $date = Carbon::today()->toDateString();

    WorkerAttendance::factory()->count(2)->create([
        'site_id' => $site->id,
        'attendance_date' => $date,
    ]);

    expect(WorkerAttendance::count())->toBe(2);
});

it('allows the same worker on different dates', function () {
    $worker = Worker::factory()->create();

    WorkerAttendance::factory()->create([
        'worker_id' => $worker->id,
        'attendance_date' => Carbon::today()->toDateString(),
    ]);

    WorkerAttendance::factory()->create([
        'worker_id' => $worker->id,
        'attendance_date' => Carbon::today()->subDay()->toDateString(),
    ]);

    expect(WorkerAttendance::count())->toBe(2);
});
