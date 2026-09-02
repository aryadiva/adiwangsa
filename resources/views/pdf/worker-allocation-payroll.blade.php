{{-- Worker Allocation & Payroll Summary — rendered by PdfReportService (App\DTOs\ReportDataDTO) --}}
<!DOCTYPE html>
<html lang="{{ $dto->locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ $dto->title }}</title>
    <style>
        @page { size: A4 portrait; margin: 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; }
        h1 { font-size: 18px; margin: 0 0 2px; color: #111827; }
        .sub { color: #6b7280; font-size: 10px; margin-bottom: 14px; }
        .header { border-bottom: 3px solid #2563eb; padding-bottom: 10px; margin-bottom: 14px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .meta td { padding: 4px 6px; border: 1px solid #e5e7eb; font-size: 10px; }
        .meta td.k { width: 30%; background: #f3f4f6; font-weight: bold; color: #374151; }
        h2 { font-size: 13px; color: #111827; margin: 16px 0 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.data th, table.data td { border: 1px solid #e5e7eb; padding: 5px 6px; text-align: left; font-size: 10px; }
        table.data th { background: #f3f4f6; color: #374151; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 4px; }
        .empty { color: #9ca3af; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $dto->title }}</h1>
        <div class="sub">{{ $dto->projectName }} ({{ $dto->projectCode }}) &middot; {{ $dto->clientCompany }}</div>
    </div>

    <table class="meta">
        <tr>
            <td class="k">{{ __('pdf.period') }}</td><td>{{ $dto->dateRange }}</td>
            <td class="k">{{ __('pdf.workers') }}</td><td>{{ $dto->workerCount }} ({{ $dto->totalHours }} {{ __('pdf.hrs') }})</td>
        </tr>
    </table>

    @if (count($dto->workerRows))
        <h2>{{ __('pdf.worker_allocation') }}</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>{{ __('pdf.name') }}</th>
                    <th>{{ __('pdf.trade') }}</th>
                    <th>{{ __('pdf.hours') }}</th>
                    <th>{{ __('pdf.days') }}</th>
                    <th>{{ __('pdf.site') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dto->workerRows as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['trade'] }}</td>
                        <td>{{ $row['hours'] }}</td>
                        <td>{{ $row['days'] }}</td>
                        <td>{{ $row['site'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="empty">{{ __('pdf.no_worker_allocations') }}</p>
    @endif

    {{-- Payroll-specific columns (regular/overtime pay from worker_attendance) are appended here in Phase 8.5. --}}

    <div class="footer">{{ __('pdf.generated') }} {{ \Illuminate\Support\Carbon::parse($dto->generatedAt)->translatedFormat('d M Y H:i') }} &middot; {{ $dto->projectCode }}</div>
</body>
</html>
