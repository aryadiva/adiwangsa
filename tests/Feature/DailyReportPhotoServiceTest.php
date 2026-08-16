<?php

use App\Models\DailyReport;
use App\Models\DailyReportPhoto;
use App\Services\DailyReportPhotoService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

uses(RefreshDatabase::class);

function photoImage(int $width = 1200, int $height = 800): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'photo').'.jpg';
    $image = imagecreatetruecolor($width, $height);
    $color = imagecolorallocate($image, 200, 50, 50);
    imagefill($image, 0, 0, $color);
    imagejpeg($image, $path);
    imagedestroy($image);

    return new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true);
}

function notAnImage(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'photo').'.jpg';
    file_put_contents($path, 'this is definitely not an image');

    return new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true);
}

it('stores an original and thumbnail and returns metadata', function () {
    Storage::fake('photos');
    $service = app(DailyReportPhotoService::class);

    $path = $service->store(photoImage());

    Storage::disk('photos')->assertExists($path);

    $meta = $service->metadataFor($path);

    Storage::disk('photos')->assertExists($meta['thumbnail_path']);

    expect($meta['file_path'])->toBe($path)
        ->and($meta['thumbnail_path'])->toContain('thumbs/')
        ->and($meta['file_size_bytes'])->toBe(Storage::disk('photos')->size($path));
});

it('downsizes the thumbnail to the configured width', function () {
    Storage::fake('photos');
    $service = app(DailyReportPhotoService::class);

    $path = $service->store(photoImage(1600, 1000));
    $meta = $service->metadataFor($path);

    $manager = app(ImageManager::class);
    $thumbnail = $manager->decode(Storage::disk('photos')->get($meta['thumbnail_path']));

    expect($thumbnail->width())->toBeLessThanOrEqual(DailyReportPhotoService::THUMBNAIL_WIDTH);
});

it('surfaces a clear error when the photo disk write silently fails', function () {
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('put')->once()->andReturn(false);
    Storage::set('photos', $disk);

    $service = app(DailyReportPhotoService::class);

    expect(fn () => $service->store(photoImage()))
        ->toThrow(RuntimeException::class, 'Could not store the photo');
});

it('rejects a file whose content is not an allowed image', function () {
    Storage::fake('photos');
    $service = app(DailyReportPhotoService::class);

    expect(fn () => $service->store(notAnImage()))->toThrow(RuntimeException::class);

    Storage::disk('photos')->assertDirectoryEmpty('daily-report-photos');
});

it('returns signed, expiring urls for display', function () {
    $report = DailyReport::factory()->create();
    $photo = DailyReportPhoto::create([
        'daily_report_id' => $report->id,
        'file_path' => 'daily-report-photos/sample.jpg',
        'thumbnail_path' => 'daily-report-photos/thumbs/sample.jpg',
        'file_size_bytes' => 1234,
    ]);

    expect($photo->signedUrl())->toContain('X-Amz-Expires=')
        ->and($photo->signedUrl(5))->toContain('X-Amz-Expires=300')
        ->and($photo->signedThumbnailUrl())->toContain('thumbs/sample.jpg')
        ->and($photo->signedThumbnailUrl())->toContain('X-Amz-Expires=');
});
