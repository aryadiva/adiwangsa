<?php

use App\Enums\DailyReportStatus;
use App\Enums\DocumentType;
use App\Enums\Locale;
use App\Enums\WeatherCondition;
use App\Jobs\GeneratePdfJob;
use App\Livewire\LanguageSwitcher;
use App\Models\User;
use App\Services\PdfDocumentService;
use App\Support\LocaleContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Number;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('defaults to English for users without a preference', function () {
    $user = adminUser();

    expect($user->fresh()->locale)->toBe('en')
        ->and(LocaleContext::language($user))->toBe(Locale::English);
});

it('persists the chosen locale per user so it survives logout and login', function () {
    $user = adminUser();
    $this->actingAs($user);

    LocaleContext::apply(Locale::Indonesian);

    expect($user->fresh()->locale)->toBe('id')
        ->and(app()->getLocale())->toBe('id');

    $this->actingAs($user->fresh());
    LocaleContext::apply(LocaleContext::language());

    expect(app()->getLocale())->toBe('id');
});

it('toggles the locale from the switcher and updates the user', function () {
    $user = adminUser();
    $this->actingAs($user);

    Livewire::test(LanguageSwitcher::class)
        ->call('toggle');

    expect($user->fresh()->locale)->toBe('id')
        ->and(LocaleContext::language())->toBe(Locale::Indonesian);

    Livewire::test(LanguageSwitcher::class)
        ->call('toggle');

    expect($user->fresh()->locale)->toBe('en');
});

it('renders money values with the IDR symbol and grouping', function () {
    $idr = preg_replace('/\s+/u', '', Number::currency(10_000, 'IDR', 'id'));

    expect($idr)->toBe('Rp10.000,00')
        ->and(Number::currency(1_500, 'IDR', 'en'))->toContain('1,500.00');
});

it('bakes the requesting user locale into the daily PDF DTO', function () {
    [, , $report] = reportWithWorkersAndPhoto();

    $user = User::factory()->admin()->create(['locale' => 'id']);
    $this->actingAs($user);

    Bus::fake();

    app(PdfDocumentService::class)->queueDaily($report, $user->id);

    Bus::assertDispatched(
        GeneratePdfJob::class,
        fn (GeneratePdfJob $job): bool => $job->dto->locale === 'id'
    );
});

it('translates enum labels according to the active locale', function () {
    app()->setLocale('id');

    expect(WeatherCondition::Sunny->getLabel())->toBe('Cerah')
        ->and(DocumentType::DailyProgress->label())->toBe('Laporan Kemajuan Harian Lokasi')
        ->and(DailyReportStatus::Published->getLabel())->toBe('Terbit');
});

it('relabels money columns to IDR', function () {
    $projects = file_get_contents(app_path('Filament/Resources/ProjectResource.php'));
    $workers = file_get_contents(app_path('Filament/Resources/WorkerResource.php'));

    expect($projects)->toContain("->money('IDR')")
        ->and($workers)->toContain("->money('IDR')")
        ->and($projects)->not->toContain("->money('USD')")
        ->and($workers)->not->toContain("->money('USD')");
});
