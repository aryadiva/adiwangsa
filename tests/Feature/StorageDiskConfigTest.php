<?php

use Illuminate\Support\Facades\Storage;

$devDiskConfig = [
    'driver' => 's3',
    'key' => 'sail',
    'secret' => 'password',
    'region' => 'us-east-1',
    'bucket' => 'construction-ops',
    'endpoint' => 'http://host.docker.internal:9000',
    'url' => 'http://host.docker.internal:9000/construction-ops',
    'use_path_style_endpoint' => true,
    'visibility' => 'private',
];

test('photos and pdfs disks use a single browser+container-addressable endpoint', function () use ($devDiskConfig) {
    config(['filesystems.disks.photos' => $devDiskConfig]);
    config(['filesystems.disks.pdfs' => $devDiskConfig]);

    foreach (['photos', 'pdfs'] as $name) {
        $disk = Storage::disk($name);
        $config = config("filesystems.disks.$name");

        $endpointHost = parse_url($config['endpoint'], PHP_URL_HOST);
        $urlHost = parse_url($disk->url('daily-report-photos/abc.jpg'), PHP_URL_HOST);

        expect($config['driver'])->toBe('s3')
            ->and($config['visibility'])->toBe('private')
            ->and($config['use_path_style_endpoint'])->toBeTrue()
            // A single address is used for BOTH SDK API calls and browser-facing
            // URLs, so no /etc/hosts entry is needed and no machine-specific IP
            // or docker-internal name (e.g. "minio") appears in a signed URL.
            ->and($endpointHost)->toBe('host.docker.internal')
            ->and($urlHost)->toBe('host.docker.internal')
            ->and($endpointHost)->not->toBeIn(['minio', 'localhost', 'laravel.test']);
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
