<?php

use App\Models\User;
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
