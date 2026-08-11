{{-- Worker Attendance & Labor Roster — rendered by PdfReportService (App\DTOs\ReportDataDTO) --}}
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

    @if (count($dto->workerRows))
        <h2>Labor Roster</h2>
        <table class="data">
            <thead>
                <tr><th>Name</th><th>Trade</th><th>Site</th><th>Days</th><th>Hours</th></tr>
            </thead>
            <tbody>
                @foreach ($dto->workerRows as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['trade'] }}</td>
                        <td>{{ $row['site'] }}</td>
                        <td>{{ $row['days'] }}</td>
                        <td>{{ $row['hours'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p><strong>Total Workers:</strong> {{ $dto->workerCount }} &middot; <strong>Total Hours:</strong> {{ $dto->totalHours }}</p>
    @else
        <p class="empty">No worker allocations in this period.</p>
    @endif

    <div class="footer">Generated {{ $dto->generatedAt }} &middot; {{ $dto->projectCode }}</div>
</body>
</html>
