<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        User::factory()->siteEngineer()->create([
            'name' => 'Engineer One',
            'email' => 'engineer1@example.com',
            'password' => bcrypt('password'),
        ]);

        User::factory()->siteEngineer()->create([
            'name' => 'Engineer Two',
            'email' => 'engineer2@example.com',
            'password' => bcrypt('password'),
        ]);

        User::factory()->client()->create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public static function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }
}
