<?php

use App\Http\Controllers\V1\TeacherApi\AuthController;
use App\Http\Controllers\V1\TeacherApi\CatalogController;
use App\Http\Controllers\V1\TeacherApi\ClassNoteController;
use App\Http\Controllers\V1\TeacherApi\ClassRoutineController;
use App\Http\Controllers\V1\TeacherApi\NoticeController;
use App\Http\Middleware\EnsureTeacherIsActive;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)
    ->prefix('auth')
    ->name('auth.')
    ->group(function () {
        Route::post('register', 'register')->middleware('throttle:teacher-auth')->name('register');
        Route::post('login', 'login')->middleware('throttle:teacher-auth')->name('login');
        Route::post('forgot-password', 'forgotPassword')->middleware('throttle:3,1')->name('forgot-password');
        Route::post('reset-password', 'resetPassword')->middleware('throttle:teacher-auth')->name('reset-password');
    });

Route::middleware(['auth:teacher', EnsureTeacherIsActive::class])->group(function () {
    Route::controller(AuthController::class)
        ->prefix('auth')
        ->name('auth.')
        ->group(function () {
            Route::get('me', 'me')->name('me');
            Route::post('logout', 'logout')->name('logout');
            Route::patch('profile', 'updateProfile')->name('profile');
        });

    Route::prefix('college')->name('college.')->group(function () {
        Route::get('subjects', [CatalogController::class, 'subjects'])->name('subjects');

        // Literal segments must stay above their apiResource.
        Route::get('notices/filters', [NoticeController::class, 'filterOptions'])->name('notices.filters');
        Route::get('notices/{notice}/attachment', [NoticeController::class, 'attachment'])->name('notices.attachment');
        Route::patch('notices/{notice}/publish', [NoticeController::class, 'publish'])->name('notices.publish');
        Route::patch('notices/{notice}/unpublish', [NoticeController::class, 'unpublish'])->name('notices.unpublish');
        Route::apiResource('notices', NoticeController::class);

        Route::get('routines/{classRoutine}/attachment', [ClassRoutineController::class, 'attachment'])
            ->name('routines.attachment');
        Route::patch('routines/{classRoutine}/publish', [ClassRoutineController::class, 'publish'])
            ->name('routines.publish');
        Route::patch('routines/{classRoutine}/unpublish', [ClassRoutineController::class, 'unpublish'])
            ->name('routines.unpublish');
        Route::apiResource('routines', ClassRoutineController::class)
            ->parameters(['routines' => 'classRoutine']);

        Route::get('notes/{classNote}/attachment', [ClassNoteController::class, 'attachment'])
            ->name('notes.attachment');
        Route::patch('notes/{classNote}/publish', [ClassNoteController::class, 'publish'])
            ->name('notes.publish');
        Route::patch('notes/{classNote}/unpublish', [ClassNoteController::class, 'unpublish'])
            ->name('notes.unpublish');
        Route::apiResource('notes', ClassNoteController::class)
            ->parameters(['notes' => 'classNote']);
    });
});
