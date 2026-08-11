<?php

namespace App\Enums;

enum ProjectMilestoneStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Delayed = 'delayed';

    public function getLabel(): string
    {
        return __('enum.milestone_status.'.$this->value);
    }
}
