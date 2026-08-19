<?php

namespace App\Support;

/**
 * App-layer validation for sibling weight sets (all milestone_sub_jobs under one
 * project_milestones, and all project_milestones under one project).
 *
 * Sets are built incrementally: any individual weight is allowed so long as it
 * never pushes the running total past 100%, and no further row may be added once
 * the siblings already total 100%. Completion to exactly 100% is enforced by the
 * persistent, non-dismissible bell notification (see
 * MilestoneWeightNotificationService), not by blocking individual saves.
 */
final class WeightValidation
{
    public const TARGET = 100.0;

    private const EPSILON = 0.0001;

    /**
     * The current sibling set is "full" once it already reaches 100% — no more
     * rows may be added to it.
     */
    public static function isFull(float $siblingSum): bool
    {
        return abs($siblingSum - self::TARGET) < self::EPSILON;
    }

    /**
     * A proposed weight is acceptable if it does not push the set past 100%.
     */
    public static function canAdd(float $siblingSum, float $newWeight): bool
    {
        return ($siblingSum + $newWeight) - self::TARGET <= self::EPSILON;
    }

    /**
     * @param  list<int|float|string|null>  $weights
     */
    public static function sum(array $weights): float
    {
        $total = 0.0;

        foreach ($weights as $weight) {
            $total += (float) $weight;
        }

        return $total;
    }
}
