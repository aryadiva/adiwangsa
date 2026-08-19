<?php

namespace App\Enums;

enum MilestoneSubJobStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Delayed = 'delayed';

    public function getLabel(): string
    {
        return __('enum.sub_job_status.'.$this->value);
    }
}
