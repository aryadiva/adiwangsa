<?php

namespace App\Enums;

enum DailyReportStatus: string
{
    case Draft = 'draft';
    case NeedApproval = 'need_approval';
    case Published = 'published';
    case RevisionRequested = 'revision_requested';
}
