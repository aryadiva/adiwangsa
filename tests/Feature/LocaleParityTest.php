<?php

use App\Enums\ReportShift;
use Illuminate\Support\Facades\File;

/**
 * Translation key parity guard (TASKS 8.8): every key that exists in the
 * English locale must exist in the Indonesian locale and vice versa —
 * including the published Filament vendor overrides — so no surface can
 * silently ship English-only (or ID-only) strings.
 */
function langKeyTree(array $array, string $prefix = ''): array
{
    $keys = [];

    foreach ($array as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

        if (is_array($value)) {
            $keys = [...$keys, ...langKeyTree($value, $path)];

            continue;
        }

        $keys[] = $path;
    }

    return $keys;
}

it('keeps lang/en and lang/id key sets identical for app-level files', function (string $file) {
    $en = langKeyTree(require lang_path('en/'.$file.'.php'));
    $id = langKeyTree(require lang_path('id/'.$file.'.php'));

    expect($id)->toBe($en, "lang/id/{$file}.php keys diverge from lang/en/{$file}.php");
})->with(['app', 'enum', 'mail', 'weather', 'pdf']);

it('keeps published Filament vendor overrides in en and id at identical key sets', function () {
    $vendorBase = lang_path('vendor');

    if (! File::isDirectory($vendorBase)) {
        $this->fail('lang/vendor is not published');
    }

    $packages = collect(File::directories($vendorBase))
        ->filter(fn (string $dir): bool => str_starts_with(basename($dir), 'filament'))
        ->values();

    foreach ($packages as $packageBase) {
        $package = basename($packageBase);
        $enRelative = collect(File::allFiles($packageBase.'/en'))
            ->map(fn ($f) => ltrim(str_replace($packageBase.'/en', '', $f->getPathname()), '/'))
            ->sort()
            ->values()
            ->all();
        $idRelative = collect(File::allFiles($packageBase.'/id'))
            ->map(fn ($f) => ltrim(str_replace($packageBase.'/id', '', $f->getPathname()), '/'))
            ->sort()
            ->values()
            ->all();

        expect($idRelative)->toBe($enRelative, "Filament vendor override file sets diverge between en and id for {$package}");

        foreach ($enRelative as $relative) {
            $enKeys = langKeyTree(require $packageBase.'/en/'.$relative);
            $idKeys = langKeyTree(require $packageBase.'/id/'.$relative);

            expect($idKeys)->toBe($enKeys, "vendor override {$package}/{$relative} keys diverge between en and id");
        }
    }
});

it('localizes the report shift enum in both locales', function () {
    app()->setLocale('en');
    expect(ReportShift::Shift1->getLabel())->toBe('Shift 1');

    app()->setLocale('id');
    expect(ReportShift::Shift1->getLabel())->toBe('Shift 1');
});
