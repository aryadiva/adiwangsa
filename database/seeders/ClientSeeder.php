<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clientUser = User::where('role', UserRole::Client)->first();

        Client::create([
            'user_id' => $clientUser?->id,
            'company_name' => 'Acme Construction Holdings',
            'contact_person' => 'Jane Doe',
            'email' => 'jane@acme.test',
            'phone' => '+6281234567890',
            'meta_data' => ['preferred_contact' => 'email'],
        ]);
    }
}
