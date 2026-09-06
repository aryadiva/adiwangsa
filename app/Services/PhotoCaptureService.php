<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use RuntimeException;

/**
 * Shared store-+-thumbnail pipeline for all live-captured photos
 * (SE progress pairs, HRD attendance) on the S3-compatible `photos` disk.
 * Content-type enforcement is by server-side MIME sniffing, never the
 * client-supplied filename.
 */
class PhotoCaptureService
{
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public const THUMBNAIL_WIDTH = 600;

    public const ATTENDANCE_DIRECTORY = 'worker-attendance-photos';

    public function __construct(
        protected ImageManager $imageManager,
        protected string $disk = 'photos',
    ) {}

    /**
     * @return array{path: string, thumbnail_path: string, file_size_bytes: int}
     */
    public function capture(UploadedFile $file, string $directory): array
    {
        $content = $this->readContent($file);

        if ($content === '') {
            throw new RuntimeException('Could not read uploaded file contents.');
        }

        $mime = $this->sniffMime($content);
        $this->assertAllowed($mime);

        $disk = Storage::disk($this->disk);
        $path = $this->pathFor($directory, $file->getClientOriginalExtension());

        if ($disk->put($path, $content) === false) {
            throw new RuntimeException('Could not store the photo on the configured photo disk.');
        }

        $this->createThumbnail($disk, $path, $content, $directory);

        return [
            'path' => $path,
            'thumbnail_path' => $this->thumbnailPathFor($path),
            'file_size_bytes' => $disk->size($path),
        ];
    }

    public function thumbnailPathFor(string $path): string
    {
        $directory = trim(dirname($path), '/');
        $base = pathinfo($path, PATHINFO_FILENAME);

        return $directory.'/thumbs/'.$base.'.jpg';
    }

    protected function readContent(UploadedFile $file): string
    {
        $realPath = $file->getRealPath();

        if (! is_string($realPath)) {
            return '';
        }

        return file_get_contents($realPath) ?: '';
    }

    protected function pathFor(string $directory, string $extension): string
    {
        $extension = $this->normalizeExtension($extension);

        return rtrim($directory, '/').'/'.Str::uuid().'.'.$extension;
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
    protected function createThumbnail($disk, string $path, string $content, string $directory): void
    {
        $image = $this->imageManager->decode($content);
        $image->scaleDown(width: self::THUMBNAIL_WIDTH);

        $encoded = $image->encodeUsingFileExtension('jpg', quality: 75);

        if ($disk->put($this->thumbnailPathFor($path), (string) $encoded) === false) {
            throw new RuntimeException('Could not store the photo thumbnail on the configured photo disk.');
        }
    }
}
