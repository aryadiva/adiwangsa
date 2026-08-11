<?php

use App\Models\Client;
use App\Models\GeneratedDocument;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createDownloadDocument(): array
{
    Storage::fake('pdfs');
    Storage::disk('pdfs')->put('documents/report.pdf', 'fake pdf content');

    $admin = User::factory()->admin()->create();
    $engineer = User::factory()->siteEngineer()->create();
    $clientUser = User::factory()->client()->create();
    $client = Client::factory()->create(['user_id' => $clientUser->id]);
    $project = Project::factory()->create(['client_id' => $client->id]);

    $document = GeneratedDocument::create([
        'project_id' => $project->id,
        'document_type' => 'weekly_digest',
        'file_path' => 'documents/report.pdf',
        'generated_by_user_id' => $admin->id,
    ]);

    return [$document, $admin, $engineer, $clientUser];
}

it('lets an admin download a generated document', function () {
    [$document, $admin] = createDownloadDocument();

    $this->actingAs($admin)
        ->get(route('generated-documents.download', $document))
        ->assertOk()
        ->assertHeader('content-disposition');
});

it('lets the owning client download a generated document for their project', function () {
    [$document, , , $clientUser] = createDownloadDocument();

    $this->actingAs($clientUser)
        ->get(route('generated-documents.download', $document))
        ->assertOk();
});

it('denies an unrelated user from downloading a generated document', function () {
    [$document, , $engineer] = createDownloadDocument();

    $this->actingAs($engineer)
        ->get(route('generated-documents.download', $document))
        ->assertForbidden();
});
