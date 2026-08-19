<?php

namespace App\Rules;

use App\Support\ScheduleValidator;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MilestoneStartDateRule implements ValidationRule
{
    public function __construct(
        private readonly ?string $projectStartDate,
    ) {}

    /**
     * A milestone must not start before its owning project's start date.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $this->projectStartDate === null) {
            return;
        }

        $start = Carbon::parse($value);
        $projectStart = Carbon::parse($this->projectStartDate);

        if (! ScheduleValidator::startDateOnOrAfter($start, $projectStart)) {
            $fail(__('Start date cannot be before the project start date (:date).', [
                'date' => $projectStart->toDateString(),
            ]));
        }
    }
}
