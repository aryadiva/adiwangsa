<?php

namespace App\Filament\Components;

use App\Rules\LiveCapture as LiveCaptureRule;
use App\Services\PhotoCaptureService;
use Carbon\Carbon;
use Filament\Forms\Components\Field;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Live camera capture form field (PRD v3 §6.2 / §6.4).
 *
 * The client-side component (resources/views/filament/forms/components/live-capture.blade.php
 * + the Alpine definition registered via the panel SCRIPTS_END render hook)
 * uses getUserMedia with an <input capture> fallback — no gallery/file-picker
 * path. It uploads the captured frame through Livewire's file-upload pipeline
 * with the shutter timestamp embedded in the filename, which
 * App\Rules\LiveCapture verifies server-side as defense in depth.
 */
class LiveCapture extends Field
{
    protected string $view = 'filament.forms.components.live-capture';

    protected ?string $captureDirectory = null;

    public function captureDirectory(string $directory): static
    {
        $this->captureDirectory = $directory;

        return $this;
    }

    public function getCaptureDirectory(): string
    {
        return $this->captureDirectory ?? 'live-captures';
    }

    /**
     * Signed preview URL for an already-stored capture (edit forms).
     */
    public function getPreviewUrl(): ?string
    {
        $state = $this->getState();

        if (! is_string($state) || $state === '' || str_starts_with($state, 'livewire-file:')) {
            return null;
        }

        try {
            return Storage::disk('photos')->temporaryUrl($state, now()->addMinutes(5));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Stores a capture state onto the photos disk and returns the metadata
     * needed to persist it, or null when the state is empty.
     *
     * Live captures (TemporaryUploadedFile / livewire-file: token) are stored
     * through the shared PhotoCaptureService; plain string paths are treated
     * as already-stored files (device-upload path for Admin) and are returned
     * without re-processing.
     *
     * @return array{path: string, thumbnail_path: string|null, file_size_bytes: int, captured_at: Carbon}|null
     */
    public static function resolveState(mixed $state, string $directory): ?array
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (is_string($state) && ! str_starts_with($state, 'livewire-file:')) {
            $disk = Storage::disk('photos');

            return [
                'path' => $state,
                'thumbnail_path' => null,
                'file_size_bytes' => $disk->exists($state) ? $disk->size($state) : 0,
                'captured_at' => now(),
            ];
        }

        $file = $state instanceof TemporaryUploadedFile
            ? $state
            : TemporaryUploadedFile::createFromLivewire(substr((string) $state, strlen('livewire-file:')));

        $captured = app(PhotoCaptureService::class)->capture(
            new UploadedFile($file->getRealPath(), $file->getClientOriginalName(), $file->getMimeType() ?: 'image/jpeg', null, true),
            $directory,
        );

        return [
            'path' => $captured['path'],
            'thumbnail_path' => $captured['thumbnail_path'],
            'file_size_bytes' => $captured['file_size_bytes'],
            'captured_at' => LiveCaptureRule::embeddedTimestamp($file) ?? now(),
        ];
    }
}
