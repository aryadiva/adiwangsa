<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Standard Workday Hours
    |--------------------------------------------------------------------------
    |
    | Hours in one standard workday. Hourly rate is derived as
    | daily_rate / standard_workday_hours; regular pay is pro-rated by
    | logged attendance hours at the same hourly rate.
    |
    */

    'standard_workday_hours' => env('STANDARD_WORKDAY_HOURS', 8),

    /*
    |--------------------------------------------------------------------------
    | Bi-Weekly Payroll Cycle
    |--------------------------------------------------------------------------
    |
    | Cycle length in days and the anchor date the 14-day cadence counts
    | from. A run for the previous cycle is generated on the first day of
    | every cycle boundary (anchor + N × cycle_days).
    |
    */

    'cycle_days' => env('PAYROLL_CYCLE_DAYS', 14),

    'cycle_anchor_date' => env('PAYROLL_CYCLE_ANCHOR', '2026-01-01'),

];
