<?php

namespace App\Rules;

use App\Models\Project;
use App\Support\WeightValidation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MilestoneWeightsTotalRule implements ValidationRule
{
    public function __construct(
        private readonly Project $project,
        private readonly ?string $excludeMilestoneId = null,
    ) {}

    /**
     * Milestones under a project are built incrementally: never push the total
     * past 100%, and never add a row once the siblings already total 100%.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $siblingSum = 0.0;

        foreach ($this->project->milestones()->get() as $milestone) {
            if ($this->excludeMilestoneId && $milestone->getKey() === $this->excludeMilestoneId) {
                continue;
            }

            $siblingSum += (float) $milestone->weight_percentage;
        }

        if (WeightValidation::isFull($siblingSum)) {
            $fail(__('Milestone weights already tally to 100%. No further milestones can be added.'));

            return;
        }

        if (! WeightValidation::canAdd($siblingSum, (float) $value)) {
            $fail(__('Milestone weights cannot exceed 100% (current total: :total%).', [
                'total' => number_format($siblingSum, 2),
            ]));
        }
    }
}
