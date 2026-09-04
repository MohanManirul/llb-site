<?php

use App\Http\Controllers\V1\PublicApi\CatalogController;
use App\Http\Controllers\V1\PublicApi\MaterialController;
use App\Http\Controllers\V1\PublicApi\MaterialFileController;
use App\Http\Controllers\V1\PublicApi\NoticeController;
use App\Http\Controllers\V1\PublicApi\PublicModelTestController;
use App\Http\Controllers\V1\PublicApi\PulseController;
use App\Http\Controllers\V1\PublicApi\QuestionArchiveController;
use Illuminate\Support\Facades\Route;

// The anonymous student-facing API. No auth, no session — see bootstrap/app.php
// for why this group deliberately avoids the `api` middleware group.

Route::controller(CatalogController::class)->group(function () {
    Route::get('programs', 'programs')->name('programs.index');
    Route::get('programs/{program:slug}', 'program')->name('programs.show');
    Route::get('sessions', 'sessions')->name('sessions.index');
    Route::get('subjects', 'subjects')->name('subjects.index');
    Route::get('subjects/{subject:slug}', 'subject')->name('subjects.show');
    Route::get('filters', 'filters')->name('filters');
});

Route::get('materials', [MaterialController::class, 'index'])->name('materials.index');
Route::get('materials/{studyMaterial:slug}', [MaterialController::class, 'show'])
    ->name('materials.show');

Route::get('materials/{studyMaterial:slug}/files/{file}/preview', [MaterialFileController::class, 'preview'])
    ->scopeBindings()
    ->name('materials.files.preview');
Route::get('materials/{studyMaterial:slug}/files/{file}/download', [MaterialFileController::class, 'download'])
    ->middleware('throttle:downloads')
    ->scopeBindings()
    ->name('materials.files.download');

Route::controller(QuestionArchiveController::class)
    ->prefix('question-archive')
    ->name('question-archive.')
    ->group(function () {
        Route::get('filters', 'filters')->name('filters');
        Route::get('mcq', 'mcq')->name('mcq');
        Route::get('written', 'written')->name('written');
    });

Route::get('model-tests', [PublicModelTestController::class, 'index'])->name('model-tests.index');
Route::get('model-tests/{modelTest:slug}', [PublicModelTestController::class, 'show'])
    ->name('model-tests.show');

Route::get('notices', [NoticeController::class, 'index'])->name('notices.index');
Route::get('notices/{notice:slug}', [NoticeController::class, 'show'])->name('notices.show');
Route::get('notices/{notice:slug}/attachment', [NoticeController::class, 'attachment'])
    ->middleware('throttle:downloads')
    ->name('notices.attachment');

Route::post('pulse', PulseController::class)->name('pulse');
