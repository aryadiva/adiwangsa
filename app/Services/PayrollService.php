<?php

namespace App\Services;

use App\Enums\PayrollRunStatus;
use App\Models\PayrollRun;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerAttendance;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Bi-weekly payroll generation (PRD §5.4).
 *
 * A payroll run covers one 14-day cycle. Cycles are anchored to
 * `config('payroll.cycle_anchor_date')`: on the first day of a new cycle the
 * command generates a run for the *previous* cycle window
 * [today - cycle_days, today - 1].
 *
 * Per PRD §5.4/§7.4, pay derives from `worker_attendance` only (never
 * `daily_report_workers`):
 *   hourly_rate  = daily_rate / standard_workday_hours (env-configurable)
 *   regular_pay  = hourly_rate × regular_hours_total
 *   overtime_pay = hourly_rate × overtime_hours_total
 *   total_pay    = regular_pay + overtime_pay
 *
 * Generation is idempotent per (period_start, period_end).
 */
class PayrollService
{
    /**
     * The cycle window that comes due on `$asOf`, or null when today is not
     * a cycle boundary (or no full cycle has elapsed yet).
     *
     * @return array{start: CarbonInterface, end: CarbonInterface}|null
     */
    public static function dueCyclePeriod(CarbonInterface $asOf): ?array
    {
        $cycleDays = max(1, (int) config('payroll.cycle_days', 14));
        $anchor = Carbon::parse((string) config('payroll.cycle_anchor_date', '2026-01-01'))->startOfDay();
        $today = $asOf->copy()->startOfDay();

        $daysSinceAnchor = (int) $anchor->diffInDays($today);

        if ($daysSinceAnchor < $cycleDays || $daysSinceAnchor % $cycleDays !== 0) {
            return null;
        }

        return [
            'start' => $today->copy()->subDays($cycleDays),
            'end' => $today->copy()->subDay(),
        ];
    }

    /**
     * Generate (or return the existing) payroll run for the given window.
     * One payroll_items row per worker with attendance in the window,
     * including workers soft-deleted after the period — their worked days
     * still earn pay.
     */
    public static function runForPeriod(CarbonInterface $start, CarbonInterface $end, ?User $generatedBy = null): PayrollRun
    {
        $existing = PayrollRun::query()
            ->where('period_start', $start->toDateString())
            ->where('period_end', $end->toDateString())
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($start, $end, $generatedBy): PayrollRun {
            $run = PayrollRun::create([
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'status' => PayrollRunStatus::Draft,
                'generated_by_user_id' => $generatedBy?->id,
            ]);

            $hours = WorkerAttendance::query()
                ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                ->selectRaw('worker_id, SUM(hours_worked) as regular_hours_total, SUM(overtime_hours) as overtime_hours_total')
                ->groupBy('worker_id')
                ->toBase()
                ->get();

            foreach ($hours as $row) {
                $worker = Worker::withTrashed()->findOrFail($row->worker_id);

                [$regularPay, $overtimePay] = self::computePay(
                    $worker,
                    (string) $row->regular_hours_total,
                    (string) $row->overtime_hours_total
                );

                $run->items()->create([
                    'worker_id' => $row->worker_id,
                    'regular_hours_total' => $row->regular_hours_total,
                    'overtime_hours_total' => $row->overtime_hours_total,
                    'regular_pay' => $regularPay,
                    'overtime_pay' => $overtimePay,
                    'total_pay' => bcadd($regularPay, $overtimePay, 2),
                ]);
            }

            return $run;
        });
    }

    /**
     * Generate the due cycle's run, if a cycle boundary was hit today.
     */
    public static function runCycleDue(CarbonInterface $asOf): ?PayrollRun
    {
        $period = self::dueCyclePeriod($asOf);

        if ($period === null) {
            return null;
        }

        return self::runForPeriod($period['start'], $period['end']);
    }

    /**
     * @return array{0: string, 1: string} [regular_pay, overtime_pay]
     */
    public static function computePay(Worker $worker, string $regularHours, string $overtimeHours): array
    {
        $hourlyRate = self::hourlyRate($worker);

        $regularPay = bcmul($hourlyRate, $regularHours, 2);
        $overtimePay = bcmul($hourlyRate, $overtimeHours, 2);

        return [$regularPay, $overtimePay];
    }

    /**
     * daily_rate / standard_workday_hours. Workers without a daily rate
     * accrue zero pay (hours are still recorded on the item).
     */
    public static function hourlyRate(Worker $worker): string
    {
        $standardHours = max(1, (string) config('payroll.standard_workday_hours', 8));
        $dailyRate = (string) ($worker->daily_rate ?? '0');

        return bcdiv($dailyRate, $standardHours, 4);
    }
}
