<?php

use App\Http\Controllers\GeneratedDocumentDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/generated-documents/{generatedDocument}/download', GeneratedDocumentDownloadController::class)
    ->middleware(['auth', 'throttle:document-downloads'])
    ->name('generated-documents.download');
