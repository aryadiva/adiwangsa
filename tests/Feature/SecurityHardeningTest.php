<?php

use App\Enums\UserRole;
use App\Filament\Client\Pages\ChangePassword;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('marks newly created client accounts to force a password change', function () {
    Livewire::actingAs(adminUser())
        ->test(CreateUser::class)
        ->fillForm([
            'name' => 'Portal Client',
            'email' => 'portal@example.com',
            'role' => UserRole::Client->value,
            'password' => 'secret1234',
        ])
        ->call('create');

    $user = User::where('email', 'portal@example.com')->firstOrFail();

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

it('redirects a client who must change their password away from the dashboard', function () {
    $client = User::factory()->client()->create(['must_change_password' => true]);

    $this->actingAs($client)
        ->get('/client/dashboard')
        ->assertRedirect(route('filament.client.pages.change-password'));
});

it('lets a client change password and clears the forced flag', function () {
    $client = User::factory()->client()->create([
        'password' => 'temporary123',
        'must_change_password' => true,
    ]);

    Livewire::actingAs($client)
        ->test(ChangePassword::class)
        ->fillForm([
            'current_password' => 'temporary123',
            'new_password' => 'newsecret123',
            'new_password_confirmation' => 'newsecret123',
        ])
        ->call('submit');

    $fresh = $client->fresh();

    expect(Hash::check('newsecret123', $fresh->password))->toBeTrue()
        ->and($fresh->must_change_password)->toBeFalse();
});

it('rate limits the generated document download route', function () {
    $middleware = Route::getRoutes()
        ->getByName('generated-documents.download')
        ->middleware();

    expect($middleware)->toContain('auth')
        ->and($middleware)->toContain('throttle:document-downloads');
});
