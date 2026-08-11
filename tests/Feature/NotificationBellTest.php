<?php

use App\Models\User;
use Filament\Livewire\DatabaseNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('enables the database notifications bell on the admin panel', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    expect(filament()->hasDatabaseNotifications())->toBeTrue();
});

it('mounts the database notifications bell for an authenticated user', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(DatabaseNotifications::class)
        ->assertOk();
});
