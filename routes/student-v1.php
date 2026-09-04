<?php

use App\Http\Controllers\V1\StudentApi\AttemptController;
use App\Http\Controllers\V1\StudentApi\AuthController;
use App\Http\Controllers\V1\StudentApi\ModelTestController;
use App\Http\Controllers\V1\StudentApi\PracticeController;
use App\Http\Middleware\EnsureStudentIsActive;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)
    ->prefix('auth')
    ->name('auth.')
    ->group(function () {
        Route::post('register', 'register')->middleware('throttle:student-auth')->name('register');
        Route::post('login', 'login')->middleware('throttle:student-auth')->name('login');
        Route::post('forgot-password', 'forgotPassword')->middleware('throttle:3,1')->name('forgot-password');
        Route::post('reset-password', 'resetPassword')->middleware('throttle:student-auth')->name('reset-password');
    });

Route::middleware(['auth:student', EnsureStudentIsActive::class])->group(function () {
    Route::controller(AuthController::class)
        ->prefix('auth')
        ->name('auth.')
        ->group(function () {
            Route::get('me', 'me')->name('me');
            Route::post('logout', 'logout')->name('logout');
            Route::patch('profile', 'updateProfile')->name('profile');
        });

    Route::controller(PracticeController::class)
        ->prefix('practice')
        ->name('practice.')
        ->group(function () {
            Route::get('subjects', 'subjects')->name('subjects');
            Route::get('questions', 'questions')->name('questions');
            Route::get('sessions', 'history')->name('sessions.index');
            Route::post('sessions', 'store')->name('sessions.store');
        });

    Route::get('model-tests', [ModelTestController::class, 'index'])->name('model-tests.index');
    Route::get('model-tests/{modelTest:slug}', [ModelTestController::class, 'show'])->name('model-tests.show');
    Route::post('model-tests/{modelTest:slug}/attempts', [AttemptController::class, 'start'])->name('model-tests.attempts.start');

    Route::controller(AttemptController::class)
        ->prefix('attempts')
        ->name('attempts.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('{attempt}', 'show')->name('show');
            Route::put('{attempt}/answers', 'saveAnswer')->name('answers');
            Route::post('{attempt}/submit', 'submit')->name('submit');
            Route::get('{attempt}/result', 'result')->name('result');
        });
});
