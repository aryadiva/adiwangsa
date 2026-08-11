<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\GeneratedDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneratedDocumentDownloadController extends Controller
{
    public function __invoke(GeneratedDocument $generatedDocument): StreamedResponse
    {
        abort_unless($this->authorized($generatedDocument), 403);

        return Storage::disk('pdfs')->download($generatedDocument->file_path);
    }

    protected function authorized(GeneratedDocument $document): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($document->generated_by_user_id === $user->id) {
            return true;
        }

        $project = $document->project ?? $document->dailyReport?->site?->project;
        $clientUser = $project?->client?->user;

        return $clientUser !== null && $clientUser->id === $user->id;
    }
}
