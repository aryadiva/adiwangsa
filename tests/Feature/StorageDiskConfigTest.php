<?php

use Illuminate\Support\Facades\Storage;

$devDiskConfig = [
    'driver' => 's3',
    'key' => 'sail',
    'secret' => 'password',
    'region' => 'us-east-1',
    'bucket' => 'construction-ops',
    'endpoint' => 'http://minio:9000',
    'url' => 'http://localhost:9000/construction-ops',
    'use_path_style_endpoint' => true,
    'visibility' => 'private',
];

test('photos and pdfs disks split S3 API endpoint from browser-facing url', function () use ($devDiskConfig) {
    config(['filesystems.disks.photos' => $devDiskConfig]);
    config(['filesystems.disks.pdfs' => $devDiskConfig]);

    foreach (['photos', 'pdfs'] as $name) {
        $disk = Storage::disk($name);
        $config = config("filesystems.disks.$name");

        expect($config['driver'])->toBe('s3')
            ->and($config['visibility'])->toBe('private')
            ->and($config['use_path_style_endpoint'])->toBeTrue()
            // SDK API calls are fed the internal MinIO host over the sail bridge...
            ->and($config['endpoint'])->toBe('http://minio:9000')
            // ...while generated URLs point at the host-published MinIO port.
            ->and(parse_url($disk->url('daily-report-photos/abc.jpg'), PHP_URL_HOST))->toBe('localhost');
    }
});

test('clearing the s3 endpoint (prod) targets real S3 URLs without an override', function () {
    config([
        'filesystems.disks.pdfs' => [
            'driver' => 's3',
            'key' => 'prod-key',
            'secret' => 'prod-secret',
            'region' => 'us-east-1',
            'bucket' => 'construction-ops',
            'endpoint' => null,
            'url' => 'https://bucket.s3.us-east-1.amazonaws.com',
            'use_path_style_endpoint' => false,
            'visibility' => 'private',
        ],
    ]);

    $disk = Storage::disk('pdfs');

    expect(config('filesystems.disks.pdfs.endpoint'))->toBeNull()
        ->and(parse_url($disk->url('pdfs/digest.pdf'), PHP_URL_HOST))
        ->toBe('bucket.s3.us-east-1.amazonaws.com');
});
