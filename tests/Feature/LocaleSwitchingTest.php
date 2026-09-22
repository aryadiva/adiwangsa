<?php

use App\Enums\DailyReportStatus;
use App\Enums\DelayEventStatus;
use App\Enums\DocumentType;
use App\Enums\Locale;
use App\Enums\WeatherCondition;
use App\Jobs\GeneratePdfJob;
use App\Livewire\LanguageSwitcher;
use App\Models\MilestoneSubJob;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\TargetDelayWarning;
use App\Models\User;
use App\Notifications\ReportSubmittedNotification;
use App\Notifications\TargetDelayWarningNotification;
use App\Rules\MilestoneWeightsTotalRule;
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

// ---------------------------------------------------------------------------
// TASKS 8.8 — coverage across all pages/views
// ---------------------------------------------------------------------------

it('renders the language switcher on the login page for guests', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('language-switcher', false);
});

it('persists a guest locale choice in the session from the login page', function () {
    Livewire::test(LanguageSwitcher::class)
        ->call('toggle');

    expect(session(LocaleContext::GUEST_SESSION_KEY))->toBe('id')
        ->and(app()->getLocale())->toBe('id');

    // A fresh request (guest) re-applies the session locale via SetLocale.
    LocaleContext::apply(LocaleContext::language());

    expect(app()->getLocale())->toBe('id');
});

it('renders the language switcher on admin pages across roles', function () {
    foreach ([adminUser(), engineerAssignedTo(Project::factory()->create()), hrdUser()] as $user) {
        $this->actingAs($user);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('language-switcher', false);
    }
});

it('localizes admin navigation and table content for the Indonesian locale', function () {
    $admin = adminUser();
    $admin->update(['locale' => 'id']);
    $this->actingAs($admin);

    $response = $this->get('/admin');
    $response->assertOk();

    expect($response->getContent())
        ->toContain('Laporan Harian')
        ->toContain('Proyek')
        ->toContain('Operasional')
        ->not->toContain('>Daily Reports<');
});

it('localizes the daily report form labels for the Indonesian locale', function () {
    [, , $report] = reportWithWorkersAndPhoto();
    $admin = adminUser();
    $admin->update(['locale' => 'id']);
    $this->actingAs($admin);

    $response = $this->get("/admin/daily-reports/{$report->id}/edit");
    $response->assertOk();

    expect($response->getContent())
        ->toContain('Target Harian')
        ->toContain('Alokasi Pekerja')
        ->toContain('Capaian Harian')
        ->not->toContain('Daily Target (system-computed)');
});

it('renders internal notification mail content in the recipient locale', function () {
    [, , $report] = reportWithWorkersAndPhoto();
    $recipient = User::factory()->admin()->create(['locale' => 'id']);

    $notification = new ReportSubmittedNotification($report);
    $mail = $notification->toMail($recipient)->toArray();

    expect($mail['subject'])->toBe('Laporan harian diajukan untuk persetujuan — '.$report->site->name)
        ->and(implode('', $mail['introLines']))->toContain('telah diajukan untuk persetujuan');
});

it('renders internal notification database payloads in the recipient locale', function () {
    $project = Project::factory()->create();
    $milestone = ProjectMilestone::factory()->create([
        'project_id' => $project->id,
        'weight_percentage' => 100,
    ]);
    $subJob = MilestoneSubJob::factory()->create([
        'project_milestone_id' => $milestone->id,
        'title' => 'Besi Tulangan',
    ]);

    $recipient = adminUser();
    $recipient->update(['locale' => 'id']);

    $warning = TargetDelayWarning::create([
        'milestone_sub_job_id' => $subJob->id,
        'project_id' => $project->id,
        'report_date' => now()->toDateString(),
        'daily_target' => '10.00',
        'actual_progress' => '4.00',
        'deficit' => '6.00',
        'first_triggered_at' => now(),
    ]);

    $data = (new TargetDelayWarningNotification($warning))->toDatabase($recipient);

    expect($data['title'])->toBe('Peringatan keterlambatan target')
        ->and($data['body'])->toContain('tertinggal dari jadwal')
        ->and($data['body'])->toContain('Besi Tulangan');
});

it('localizes the delay event status labels in both locales', function () {
    app()->setLocale('en');
    expect(DelayEventStatus::Red->label())->toBe('Red — Delay Detected');

    app()->setLocale('id');
    expect(DelayEventStatus::Red->label())->toBe('Merah — Keterlambatan Terdeteksi')
        ->and(DelayEventStatus::Yellow->label())->toBe('Kuning — Rencana Mitigasi Diajukan');

    app()->setLocale('en');
});

it('localizes milestone weights validation messages per active locale', function () {
    $project = Project::factory()->create();
    ProjectMilestone::factory()->create([
        'project_id' => $project->id,
        'weight_percentage' => 80,
    ]);

    app()->setLocale('id');

    $rule = new MilestoneWeightsTotalRule($project);

    $failMessage = null;
    $rule->validate('weight_percentage', 30.0, function ($message) use (&$failMessage) {
        $failMessage = $message;
    });

    expect($failMessage)->toBe('Bobot milestone tidak boleh melebihi 100% (total saat ini: 80.00%).');

    app()->setLocale('en');
});
