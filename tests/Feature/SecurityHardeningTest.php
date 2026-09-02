<?php

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('marks newly created client accounts to force a password change', function () {
    Livewire::actingAs(adminUser())
        ->test(CreateUser::class)
        ->fillForm([
            'name' => 'Record Client',
            'email' => 'record-client@example.com',
            'role' => UserRole::Client->value,
            'password' => 'secret1234',
        ])
        ->call('create');

    $user = User::where('email', 'record-client@example.com')->firstOrFail();

    expect($user->must_change_password)->toBeTrue();
});

it('does not force a password change for engineer accounts', function () {
    Livewire::actingAs(adminUser())
        ->test(CreateUser::class)
        ->fillForm([
            'name' => 'Site Engineer',
            'email' => 'engineer@example.com',
            'role' => UserRole::SiteEngineer->value,
            'password' => 'secret1234',
        ])
        ->call('create');

    $user = User::where('email', 'engineer@example.com')->firstOrFail();

    expect($user->must_change_password)->toBeFalse();
});

it('denies a client the admin panel — clients cannot log in anywhere (v3)', function () {
    $client = User::factory()->client()->create(['must_change_password' => true]);

    $this->actingAs($client)->get('/admin')->assertForbidden();
    $this->actingAs($client)->get('/client/dashboard')->assertNotFound();
});

it('rate limits the generated document download route', function () {
    $middleware = Route::getRoutes()
        ->getByName('generated-documents.download')
        ->middleware();

    expect($middleware)->toContain('auth')
        ->and($middleware)->toContain('throttle:document-downloads');
});
