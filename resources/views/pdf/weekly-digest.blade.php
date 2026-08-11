{{-- Weekly Site Executive Digest — released by PdfReportService (App\DTOs\ReportDataDTO) --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $dto->title }}</title>
    <style>
        @page { size: A4 portrait; margin: 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; }
        h1 { font-size: 18px; margin: 0 0 2px; color: #111827; }
        .sub { color: #6b7280; font-size: 10px; margin-bottom: 14px; }
        .header { border-bottom: 3px solid #2563eb; padding-bottom: 10px; margin-bottom: 14px; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .summary td { padding: 6px; border: 1px solid #e5e7eb; font-size: 12px; text-align: center; }
        .summary .num { font-size: 18px; font-weight: bold; color: #111827; }
        .summary .lbl { font-size: 9px; color: #6b7280; text-transform: uppercase; }
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
        <div class="sub">{{ $dto->projectName }} ({{ $dto->projectCode }}) &middot; {{ $dto->clientCompany }} &middot; {{ $dto->dateRange }}</div>
    </div>

    <table class="summary">
        <tr>
            <td><div class="num">{{ count($dto->reportSummaries) }}</div><div class="lbl">Reports</div></td>
            <td><div class="num">{{ $dto->workerCount }}</div><div class="lbl">Worker-Days</div></td>
            <td><div class="num">{{ $dto->totalHours }}</div><div class="lbl">Hours</div></td>
            <td><div class="num">{{ count($dto->milestones) }}</div><div class="lbl">Milestones</div></td>
        </tr>
    </table>

    @if (count($dto->reportSummaries))
        <h2>Daily Summaries (Published Reports)</h2>
        <table class="data">
            <thead>
                <tr><th>Date</th><th>Site</th><th>Weather</th><th>Hours</th><th>Summary</th><th>Delays</th></tr>
            </thead>
            <tbody>
                @foreach ($dto->reportSummaries as $row)
                    <tr>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['site'] }}</td>
                        <td>{{ ucfirst((string) $row['weather']) }}</td>
                        <td>{{ $row['hours'] }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($row['summary'], 120) }}</td>
                        <td>{{ $row['delay'] ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="empty">No published reports in this period.</p>
    @endif

    @if (count($dto->milestones))
        <h2>Milestones Completed</h2>
        <table class="data">
            <thead><tr><th>Milestone</th><th>Completed</th></tr></thead>
            <tbody>
                @foreach ($dto->milestones as $item)
                    <tr>
                        <td>{{ $item['title'] }}</td>
                        <td>{{ $item['completed_at'] ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">Generated {{ $dto->generatedAt }} &middot; {{ $dto->projectCode }}</div>
</body>
</html>
