<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Site Engineer progress-photo pipeline (daily-report-photos directory).
 * Delegates storage/thumbnails/MIME sniffing to the shared PhotoCaptureService.
 */
class DailyReportPhotoService extends PhotoCaptureService
{
    public const DIRECTORY = 'daily-report-photos';

    /**
     * @return array{before_file_path: string, before_thumbnail_path: string, file_size_bytes: int}
     */
    public function metadataFor(string $path): array
    {
        return [
            'before_file_path' => $path,
            'before_thumbnail_path' => $this->thumbnailPathFor($path),
            'file_size_bytes' => Storage::disk($this->disk)->size($path),
        ];
    }

    /**
     * @return array{path: string, thumbnail_path: string, file_size_bytes: int}
     */
    public function captureForReport(UploadedFile $file): array
    {
        return $this->capture($file, self::DIRECTORY);
    }

    /**
     * Convenience wrapper preserving the original single-file API.
     */
    public function store(UploadedFile $file): string
    {
        return $this->captureForReport($file)['path'];
    }
}
