<?php

return [
    'daily_report_status' => [
        'draft' => 'Draft',
        'need_approval' => 'Needs Approval',
        'published' => 'Published',
        'revision_requested' => 'Revision Requested',
    ],
    'project_status' => [
        'planning' => 'Planning',
        'active' => 'Active',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
    ],
    'milestone_status' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'delayed' => 'Delayed',
    ],
    'sub_job_status' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'delayed' => 'Delayed',
    ],
    'delay_event_status' => [
        'red' => 'Red — Delay Detected',
        'yellow' => 'Yellow — Mitigation Submitted',
        'green' => 'Green — Recovered',
    ],
    'payroll_run_status' => [
        'draft' => 'Draft',
        'pending_review' => 'Pending Review',
        'approved' => 'Approved',
        'paid' => 'Paid',
    ],
    'document_type' => [
        'daily_progress' => 'Daily Site Progress Report',
        'weekly_digest' => 'Weekly Site Executive Digest',
        'attendance_roster' => 'Worker Attendance & Labor Roster',
        'worker_allocation_payroll' => 'Worker Allocation & Payroll Summary',
    ],
    'user_role' => [
        'admin' => 'Admin',
        'site_engineer' => 'Site Engineer',
        'hrd' => 'HRD',
        'client' => 'Client',
    ],
];
