<?php

use App\Enums\PayrollRunStatus;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('walks the legal lifecycle draft → pending_review → approved → paid', function () {
    $admin = adminUser();
    $run = PayrollRun::factory()->create();

    $run->submitForReview();
    expect($run->refresh()->status)->toBe(PayrollRunStatus::PendingReview);

    $run->approve($admin);
    expect($run->refresh()->status)->toBe(PayrollRunStatus::Approved)
        ->and($run->approved_by_user_id)->toBe($admin->id);

    $run->markPaid();
    expect($run->refresh()->status)->toBe(PayrollRunStatus::Paid);
});

it('rejects approving a draft run', function () {
    $run = PayrollRun::factory()->create();

    expect(fn () => $run->approve(adminUser()))
        ->toThrow(DomainException::class);
});

it('rejects marking a draft run paid directly', function () {
    $run = PayrollRun::factory()->create();

    expect(fn () => $run->markPaid())
        ->toThrow(DomainException::class);
});

it('rejects submitting a pending_review run again', function () {
    $run = PayrollRun::factory()->pendingReview()->create();

    expect(fn () => $run->submitForReview())
        ->toThrow(DomainException::class);
});

it('rejects marking a pending_review run paid before approval', function () {
    $run = PayrollRun::factory()->pendingReview()->create();

    expect(fn () => $run->markPaid())
        ->toThrow(DomainException::class);
});

it('rejects any transition out of approved except mark paid', function () {
    $run = PayrollRun::factory()->approved()->create();

    expect(fn () => $run->submitForReview())
        ->toThrow(DomainException::class);

    expect(fn () => $run->approve(adminUser()))
        ->toThrow(DomainException::class);
});

it('treats paid as terminal', function () {
    $run = PayrollRun::factory()->paid()->create();

    expect(fn () => $run->submitForReview())
        ->toThrow(DomainException::class);

    expect(fn () => $run->approve(adminUser()))
        ->toThrow(DomainException::class);

    expect(fn () => $run->markPaid())
        ->toThrow(DomainException::class);
});

it('records the approving admin for audit', function () {
    $approver = User::factory()->admin()->create();
    $run = PayrollRun::factory()->pendingReview()->create();

    $run->approve($approver);

    expect($run->refresh()->approved_by_user_id)->toBe($approver->id);
});
