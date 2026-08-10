<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $engineers = User::where('role', UserRole::SiteEngineer)->get();

        $projects = Project::factory(2)->create();

        $projects->each(function (Project $project) use ($engineers) {
            $project->engineers()->attach($engineers->pluck('id')->all());
        });
    }
}
