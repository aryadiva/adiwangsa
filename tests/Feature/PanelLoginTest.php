<?php

use App\Models\Client;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Auth\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lets an admin authenticate to the panel', function () {
    $admin = User::factory()->admin()->create(['password' => bcrypt('secret123')]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $admin->email,
            'password' => 'secret123',
        ])
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect('/admin');

    expect(auth()->id())->toBe($admin->id);
});

it('rejects invalid panel credentials', function () {
    User::factory()->admin()->create(['password' => bcrypt('secret123')]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])
        ->call('authenticate')
        ->assertHasErrors();

    expect(auth()->check())->toBeFalse();
});

it('does not grant panel access to an inactive user', function () {
    $user = User::factory()->siteEngineer()->create(['is_active' => false, 'password' => bcrypt('secret123')]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('lets a client authenticate to the client panel', function () {
    Filament::setCurrentPanel(Filament::getPanel('client'));

    $user = User::factory()->client()->create(['must_change_password' => false]);
    Client::factory()->for($user)->create();

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(auth()->id())->toBe($user->id);
});

it('rejects valid client credentials on the admin panel', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $user = User::factory()->client()->create(['must_change_password' => false]);
    Client::factory()->for($user)->create();

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasErrors(['data.email']);

    expect(auth()->check())->toBeFalse();
});
