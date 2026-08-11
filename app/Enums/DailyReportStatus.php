<?php

namespace App\Enums;

enum DailyReportStatus: string
{
    case Draft = 'draft';
    case NeedApproval = 'need_approval';
    case Published = 'published';
    case RevisionRequested = 'revision_requested';

    public function getLabel(): string
    {
        return __('enum.daily_report_status.'.$this->value);
    }
}
