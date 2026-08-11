<?php

namespace App\Services;

use App\DTOs\ReportDataDTO;
use App\Enums\DocumentType;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Carbon;

/**
 * Renders a ReportDataDTO through the matching Blade template in
 * resources/views/pdf/ and returns raw PDF bytes. Never contains inline HTML.
 */
class PdfReportService
{
    public function __construct(protected PDF $pdf) {}

    public function render(ReportDataDTO $dto): string
    {
        app()->setLocale($dto->locale);
        Carbon::setLocale($dto->locale);

        $view = match ($dto->type) {
            DocumentType::DailyProgress => 'pdf.daily-progress',
            DocumentType::WeeklyDigest => 'pdf.weekly-digest',
            DocumentType::AttendanceRoster => 'pdf.attendance-roster',
        };

        return $this->pdf->loadView($view, ['dto' => $dto])->output();
    }
}
