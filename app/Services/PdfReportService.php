<?php

namespace App\Services;

use App\DTOs\ReportDataDTO;
use App\Enums\DocumentType;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Renders a ReportDataDTO through the matching Blade template in
 * resources/views/pdf/ and returns raw PDF bytes. Never contains inline HTML.
 */
class PdfReportService
{
    public function render(ReportDataDTO $dto): string
    {
        $view = match ($dto->type) {
            DocumentType::DailyProgress => 'pdf.daily-progress',
            DocumentType::WeeklyDigest => 'pdf.weekly-digest',
            DocumentType::AttendanceRoster => 'pdf.attendance-roster',
        };

        return Pdf::loadView($view, ['dto' => $dto])->output();
    }
}
