<?php

namespace App\Support;

use DateTimeInterface;

/**
 * Date-ordering guard for project milestone scheduling. Kept as a shared
 * helper so the Filament field rule and the unit tests exercise one truth
 * (same rationale as WeightValidation).
 */
final class ScheduleValidator
{
    /**
     * A milestone must not start before its owning project's start date.
     */
    public static function startDateOnOrAfter(?DateTimeInterface $startDate, ?DateTimeInterface $projectStart): bool
    {
        if ($startDate === null || $projectStart === null) {
            return true;
        }

        return ! $startDate->lt($projectStart);
    }
}
