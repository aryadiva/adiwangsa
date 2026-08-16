<?php

use App\Enums\DocumentType;
use App\Filament\Resources\GeneratedDocumentResource\Pages\ListGeneratedDocuments;
use App\Models\GeneratedDocument;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function generatedDoc(Project $project, DocumentType $type = DocumentType::WeeklyDigest): GeneratedDocument
{
    return GeneratedDocument::create([
        'project_id' => $project->id,
        'document_type' => $type->value,
        'file_path' => 'documents/test.pdf',
    ]);
}

it('lets an admin list and see generated documents', function () {
    $admin = adminUser();
    $project = Project::factory()->create(['name' => 'Alpha Complex']);
    generatedDoc($project);

    $this->actingAs($admin)
        ->get('/admin/generated-documents')
        ->assertOk()
        ->assertSee('Alpha Complex')
        ->assertSee('Weekly Site Executive Digest');
});

it('scopes generated documents for a site engineer to assigned projects only', function () {
    $assigned = Project::factory()->create(['name' => 'Assigned Tower']);
    $foreign = Project::factory()->create(['name' => 'Foreign Facility']);
    $engineer = engineerAssignedTo($assigned);

    generatedDoc($assigned);
    generatedDoc($foreign);

    $this->actingAs($engineer)
        ->get('/admin/generated-documents')
        ->assertOk()
        ->assertSee('Assigned Tower')
        ->assertDontSee('Foreign Facility');
});

it('delete action removes the pdf from storage and soft-deletes the record', function () {
    Storage::fake('pdfs');
    $admin = adminUser();
    $project = Project::factory()->create();
    $doc = generatedDoc($project);
    Storage::disk('pdfs')->put($doc->file_path, 'pdf bytes');

    Livewire::actingAs($admin)
        ->test(ListGeneratedDocuments::class)
        ->callTableAction('delete', $doc)
        ->assertSuccessful();

    Storage::disk('pdfs')->assertMissing($doc->file_path);
    expect($doc->fresh()->trashed())->toBeTrue();
});

it('delete action succeeds when the pdf is already missing, deleting just the record', function () {
    Storage::fake('pdfs');
    $admin = adminUser();
    $project = Project::factory()->create();
    $doc = generatedDoc($project);

    Storage::disk('pdfs')->assertMissing($doc->file_path);
    Livewire::actingAs($admin)
        ->test(ListGeneratedDocuments::class)
        ->callTableAction('delete', $doc)
        ->assertSuccessful();

    expect($doc->fresh()->trashed())->toBeTrue();
});
