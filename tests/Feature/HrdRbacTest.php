<?php

use App\Enums\DelayEventStatus;
use App\Enums\UserRole;
use App\Filament\Resources\WorkerAttendanceResource\Pages\ListWorkerAttendance;
use App\Models\DailyReport;
use App\Models\MilestoneSubJob;
use App\Models\PayrollRun;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Site;
use App\Models\SubJobDelayEvent;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerAttendance;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->hrd = hrdUser();
    $this->admin = adminUser();
    $this->engineer = User::factory()->siteEngineer()->create();
    $this->client = User::factory()->client()->create();

    $this->project = Project::factory()->create();
    $this->engineer->projects()->attach($this->project);
    $this->site = Site::factory()->create(['project_id' => $this->project->id]);
    $this->worker = Worker::factory()->create();

    $this->attendance = WorkerAttendance::factory()->create([
        'worker_id' => $this->worker->id,
        'site_id' => $this->site->id,
        'recorded_by_user_id' => $this->hrd->id,
    ]);

    $this->report = DailyReport::factory()->create([
        'site_id' => $this->site->id,
        'created_by_user_id' => $this->engineer->id,
    ]);
});

function panel(): Panel
{
    return Filament::getPanel('admin');
}

it('keeps hrd as a panel-accessible role on the admin panel', function () {
    expect($this->hrd->role)->toBe(UserRole::Hrd)
        ->and($this->hrd->canAccessPanel(panel()))->toBeTrue();
});

it('denies hrd all access to daily_reports, including by guessed UUID', function () {
    expect($this->hrd->can('viewAny', DailyReport::class))->toBeFalse()
        ->and($this->hrd->can('view', $this->report))->toBeFalse()
        ->and($this->hrd->can('create', DailyReport::class))->toBeFalse()
        ->and($this->hrd->can('update', $this->report))->toBeFalse()
        ->and($this->hrd->can('delete', $this->report))->toBeFalse();
});

it('hides daily reports from the hrd user even when they hit the edit page URL', function () {
    $this->actingAs($this->hrd)
        ->get('/admin/daily-reports/'.$this->report->getRouteKey().'/edit')
        ->assertNotFound();
});

it('denies hrd access to projects, sites, milestones, sub-jobs and delay events', function () {
    $milestone = ProjectMilestone::factory()->create(['project_id' => $this->project->id]);
    $subJob = MilestoneSubJob::factory()->create(['project_milestone_id' => $milestone->id]);
    $delay = SubJobDelayEvent::create([
        'milestone_sub_job_id' => $subJob->id,
        'status' => DelayEventStatus::Red,
        'triggered_at' => now(),
        'delay_days' => 3,
    ]);

    expect($this->hrd->can('viewAny', Project::class))->toBeFalse()
        ->and($this->hrd->can('viewAny', Site::class))->toBeFalse()
        ->and($this->hrd->can('view', $this->project))->toBeFalse()
        ->and($this->hrd->can('view', $this->site))->toBeFalse()
        ->and($this->hrd->can('viewAny', ProjectMilestone::class))->toBeFalse()
        ->and($this->hrd->can('view', $milestone))->toBeFalse()
        ->and($this->hrd->can('viewAny', MilestoneSubJob::class))->toBeFalse()
        ->and($this->hrd->can('view', $subJob))->toBeFalse()
        ->and($this->hrd->can('viewAny', SubJobDelayEvent::class))->toBeFalse()
        ->and($this->hrd->can('view', $delay))->toBeFalse();
});

it('denies hrd access to payroll runs', function () {
    $run = PayrollRun::factory()->create();

    expect($this->hrd->can('viewAny', PayrollRun::class))->toBeFalse()
        ->and($this->hrd->can('view', $run))->toBeFalse();
});

it('lets hrd list, view, create, update and delete worker attendance records', function () {
    expect($this->hrd->can('viewAny', WorkerAttendance::class))->toBeTrue()
        ->and($this->hrd->can('view', $this->attendance))->toBeTrue()
        ->and($this->hrd->can('create', WorkerAttendance::class))->toBeTrue()
        ->and($this->hrd->can('update', $this->attendance))->toBeTrue()
        ->and($this->hrd->can('delete', $this->attendance))->toBeTrue();
});

it('lets hrd manage attendance for any recorder, not only their own submissions', function () {
    $otherHrd = hrdUser();
    $otherWorker = Worker::factory()->create();
    $attendance = WorkerAttendance::factory()->create([
        'worker_id' => $otherWorker->id,
        'site_id' => $this->site->id,
        'recorded_by_user_id' => $otherHrd->id,
    ]);

    expect($this->hrd->can('view', $attendance))->toBeTrue()
        ->and($this->hrd->can('update', $attendance))->toBeTrue()
        ->and($this->hrd->can('delete', $attendance))->toBeTrue();
});

it('denies a site engineer all access to worker attendance, including by guessed UUID', function () {
    expect($this->engineer->can('viewAny', WorkerAttendance::class))->toBeFalse()
        ->and($this->engineer->can('view', $this->attendance))->toBeFalse()
        ->and($this->engineer->can('create', WorkerAttendance::class))->toBeFalse()
        ->and($this->engineer->can('update', $this->attendance))->toBeFalse()
        ->and($this->engineer->can('delete', $this->attendance))->toBeFalse();
});

it('hides worker attendance from a site engineer even when they hit the edit page URL', function () {
    $this->actingAs($this->engineer)
        ->get('/admin/worker-attendances/'.$this->attendance->getRouteKey().'/edit')
        ->assertForbidden();
});

it('denies a client all access to worker attendance', function () {
    expect($this->client->can('viewAny', WorkerAttendance::class))->toBeFalse()
        ->and($this->client->can('view', $this->attendance))->toBeFalse()
        ->and($this->client->can('create', WorkerAttendance::class))->toBeFalse();
});

it('lets an admin list and manage worker attendance', function () {
    expect($this->admin->can('viewAny', WorkerAttendance::class))->toBeTrue()
        ->and($this->admin->can('view', $this->attendance))->toBeTrue()
        ->and($this->admin->can('create', WorkerAttendance::class))->toBeTrue()
        ->and($this->admin->can('update', $this->attendance))->toBeTrue()
        ->and($this->admin->can('delete', $this->attendance))->toBeTrue();
});

it('scopes the worker attendance resource list for hrd and hides it from site engineers', function () {
    Livewire::actingAs($this->hrd)
        ->test(ListWorkerAttendance::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$this->attendance]);

    $this->actingAs($this->engineer)
        ->get('/admin/worker-attendances')
        ->assertForbidden();
});
