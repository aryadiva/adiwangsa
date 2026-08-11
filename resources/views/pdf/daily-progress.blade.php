{{-- Daily Site Progress Report — rendered by PdfReportService (App\DTOs\ReportDataDTO) --}}
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
        .text { font-size: 11px; line-height: 1.5; margin: 0 0 8px; white-space: pre-wrap; }
        .photos { width: 100%; border-spacing: 6px; border-collapse: separate; }
        .photos td { width: 50%; text-align: center; vertical-align: top; }
        .photos img { width: 100%; border: 1px solid #d1d5db; border-radius: 4px; }
        .caption { font-size: 9px; color: #6b7280; margin-top: 2px; }
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
            <td class="k">{{ __('pdf.site') }}</td><td>{{ $dto->siteName }}</td>
            <td class="k">{{ __('pdf.report_date') }}</td><td>{{ \Illuminate\Support\Carbon::parse($dto->reportDate)->translatedFormat('d M Y') }}</td>
        </tr>
        <tr>
            <td class="k">{{ __('pdf.weather') }}</td><td>{{ __('weather.'.$dto->weather) }}</td>
            <td class="k">{{ __('pdf.workers') }}</td><td>{{ $dto->workerCount }} ({{ $dto->totalHours }} {{ __('pdf.hrs') }})</td>
        </tr>
    </table>

    <h2>{{ __('pdf.work_summary') }}</h2>
    <p class="text">{{ $dto->workSummary ?: '<span class="empty">'.__('pdf.no_summary').'</span>' }}</p>

    @if ($dto->delaysOrIssues)
        <h2>{{ __('pdf.delays_issues') }}</h2>
        <p class="text">{{ $dto->delaysOrIssues }}</p>
    @endif

    @if (count($dto->workerRows))
        <h2>{{ __('pdf.worker_allocation') }}</h2>
        <table class="data">
            <thead>
                <tr><th>{{ __('pdf.name') }}</th><th>{{ __('pdf.trade') }}</th><th>{{ __('pdf.hours') }}</th><th>{{ __('pdf.remarks') }}</th></tr>
            </thead>
            <tbody>
                @foreach ($dto->workerRows as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['trade'] }}</td>
                        <td>{{ $row['hours'] }}</td>
                        <td>{{ $row['remarks'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (count($dto->photos))
        <h2>{{ __('pdf.site_photos') }}</h2>
        <table class="photos">
            @foreach (array_chunk($dto->photos, 2) as $chunk)
                <tr>
                    @foreach ($chunk as $photo)
                        <td>
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('photos')->temporaryUrl($photo['path'], now()->addMinutes(15)) }}" alt="{{ __('pdf.site_photo_alt') }}">
                            @if ($photo['caption'])
                                <div class="caption">{{ $photo['caption'] }}</div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @endif

    @if (count($dto->metaData))
        <h2>{{ __('pdf.additional_data') }}</h2>
        <table class="data">
            <thead><tr><th>{{ __('pdf.field') }}</th><th>{{ __('pdf.value') }}</th></tr></thead>
            <tbody>
                @foreach ($dto->metaData as $key => $value)
                    <tr>
                        <td>{{ ucwords(str_replace(['_', '-'], ' ', (string) $key)) }}</td>
                        <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">{{ __('pdf.generated') }} {{ \Illuminate\Support\Carbon::parse($dto->generatedAt)->translatedFormat('d M Y H:i') }} &middot; {{ $dto->projectCode }}</div>
</body>
</html>
