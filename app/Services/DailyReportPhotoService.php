<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use RuntimeException;

class DailyReportPhotoService
{
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public const THUMBNAIL_WIDTH = 600;

    public function __construct(
        protected ImageManager $imageManager,
        protected string $disk = 'photos',
    ) {}

    public function store(UploadedFile $file): string
    {
        $content = $this->readContent($file);

        if ($content === '') {
            throw new RuntimeException('Could not read uploaded file contents.');
        }

        $mime = $this->sniffMime($content);
        $this->assertAllowed($mime);

        $disk = Storage::disk($this->disk);
        $path = $this->originalPathFor($file->getClientOriginalExtension());

        $disk->put($path, $content);

        $this->createThumbnail($disk, $path, $content);

        return $path;
    }

    /**
     * @return array{file_path: string, thumbnail_path: string, file_size_bytes: int}
     */
    public function metadataFor(string $path): array
    {
        $disk = Storage::disk($this->disk);

        return [
            'file_path' => $path,
            'thumbnail_path' => $this->thumbnailPathFor($path),
            'file_size_bytes' => $disk->size($path),
        ];
    }

    public function thumbnailPathFor(string $path): string
    {
        $base = pathinfo($path, PATHINFO_FILENAME);

        return 'daily-report-photos/thumbs/'.$base.'.jpg';
    }

    protected function readContent(UploadedFile $file): string
    {
        $realPath = $file->getRealPath();

        if (! is_string($realPath)) {
            return '';
        }

        return file_get_contents($realPath) ?: '';
    }

    protected function originalPathFor(string $extension): string
    {
        $extension = $this->normalizeExtension($extension);

        return 'daily-report-photos/'.Str::uuid().'.'.$extension;
    }

    protected function normalizeExtension(string $extension): string
    {
        return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
            ? strtolower($extension)
            : 'jpg';
    }

    protected function sniffMime(string $content): string
    {
        return (new \finfo(FILEINFO_MIME_TYPE))->buffer($content);
    }

    protected function assertAllowed(string $mime): void
    {
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new RuntimeException("File type [{$mime}] is not an allowed image.");
        }
    }

    /**
     * @param  Filesystem  $disk
     */
    protected function createThumbnail($disk, string $path, string $content): void
    {
        $image = $this->imageManager->decode($content);
        $image->scaleDown(width: self::THUMBNAIL_WIDTH);

        $encoded = $image->encodeUsingFileExtension('jpg', quality: 75);

        $disk->put($this->thumbnailPathFor($path), (string) $encoded);
    }
}
