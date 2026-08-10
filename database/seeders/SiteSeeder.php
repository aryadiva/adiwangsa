<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        Project::query()->each(function (Project $project) {
            Site::factory(2)->create(['project_id' => $project->id]);
        });
    }
}
