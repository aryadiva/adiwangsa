<?php

namespace App\Rules;

use App\Support\WeightValidation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SubJobsWeightsTotalRule implements ValidationRule
{
    /**
     * Sub-jobs under a milestone are built incrementally: never push the total
     * past 100%, and never add a row once the existing rows already total 100%.
     * $value is the Repeater's state: an array of row data.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || $value === []) {
            return;
        }

        $rows = array_values($value);
        $currentRow = array_pop($rows) ?? [];
        $currentRowWeight = (float) ($currentRow['weight_percentage'] ?? 0);

        $siblingSum = WeightValidation::sum(array_map(
            fn (array $row): mixed => $row['weight_percentage'] ?? 0,
            $rows,
        ));

        if (WeightValidation::isFull($siblingSum)) {
            $fail(__('Sub-job weights already tally to 100%. No further sub-jobs can be added.'));

            return;
        }

        if (! WeightValidation::canAdd($siblingSum, $currentRowWeight)) {
            $fail(__('Sub-job weights cannot exceed 100% (current total: :total%).', [
                'total' => number_format($siblingSum, 2),
            ]));
        }
    }
}
