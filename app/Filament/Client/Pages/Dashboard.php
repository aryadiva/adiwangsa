<?php

namespace App\Filament\Client\Pages;

use App\Enums\DocumentType;
use App\Models\DailyReport;
use App\Models\Project;
use App\Models\User;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string $view = 'filament.client.dashboard';

    /**
     * @return array{projects: mixed, reports: mixed}
     */
    public function getViewData(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        $projects = Project::query()
            ->where('client_id', $user?->client?->id)
            ->withCount('sites')
            ->get();

        $reports = DailyReport::forClient($user)
            ->with(['site.project', 'generatedDocuments'])
            ->get()
            ->map(fn (DailyReport $report): array => [
                'report' => $report,
                'pdf_url' => $report->generatedDocuments
                    ->where('document_type', DocumentType::DailyProgress)
                    ->sortByDesc('created_at')
                    ->first()?->signedUrl(),
            ]);

        return [
            'projects' => $projects,
            'reports' => $reports,
        ];
    }
}
