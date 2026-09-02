<?php

use App\Models\PayrollRun;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an admin to list the payroll runs resource', function () {
    $admin = adminUser();
    PayrollRun::factory()->count(2)->create();

    $this->actingAs($admin)->get('/admin/payroll-runs')->assertOk();
});

it('allows an admin to view a payroll run', function () {
    $admin = adminUser();
    $run = PayrollRun::factory()->create();

    $this->actingAs($admin)->get("/admin/payroll-runs/{$run->id}")->assertOk();
});

it('denies a site engineer the payroll runs list', function () {
    $engineer = engineerAssignedTo(Project::factory()->create());

    $this->actingAs($engineer)->get('/admin/payroll-runs')->assertForbidden();
});

it('denies a site engineer a payroll run by UUID', function () {
    $engineer = engineerAssignedTo(Project::factory()->create());
    $run = PayrollRun::factory()->create();

    $this->actingAs($engineer)->get("/admin/payroll-runs/{$run->id}")->assertForbidden();
});

it('denies a client the payroll runs list', function () {
    [$clientUser] = clientLinkedTo(Project::factory()->create());

    $this->actingAs($clientUser)->get('/admin/payroll-runs')->assertForbidden();
});

it('denies a client a payroll run by UUID', function () {
    [$clientUser] = clientLinkedTo(Project::factory()->create());
    $run = PayrollRun::factory()->create();

    $this->actingAs($clientUser)->get("/admin/payroll-runs/{$run->id}")->assertForbidden();
});

it('forbids creating payroll runs through the policy — they are system-generated', function () {
    $admin = adminUser();

    expect($admin->can('create', PayrollRun::class))->toBeFalse();
});

it('gates the lifecycle actions by status and role', function () {
    $admin = adminUser();
    $engineer = engineerAssignedTo(Project::factory()->create());

    $draft = PayrollRun::factory()->create();
    expect($admin->can('submitForReview', $draft))->toBeTrue()
        ->and($admin->can('approve', $draft))->toBeFalse()
        ->and($admin->can('markPaid', $draft))->toBeFalse()
        ->and($engineer->can('submitForReview', $draft))->toBeFalse()
        ->and($engineer->can('approve', $draft))->toBeFalse();

    $pending = PayrollRun::factory()->pendingReview()->create();
    expect($admin->can('approve', $pending))->toBeTrue()
        ->and($admin->can('submitForReview', $pending))->toBeFalse()
        ->and($admin->can('markPaid', $pending))->toBeFalse()
        ->and($engineer->can('approve', $pending))->toBeFalse();

    $approved = PayrollRun::factory()->approved()->create();
    expect($admin->can('markPaid', $approved))->toBeTrue()
        ->and($admin->can('approve', $approved))->toBeFalse()
        ->and($engineer->can('markPaid', $approved))->toBeFalse();
});
