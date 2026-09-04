<?php

use App\Http\Controllers\Admin\ActivityLogPageController;
use App\Http\Controllers\Admin\RolePageController;
use App\Http\Controllers\Admin\UserPageController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ImpersonationController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Admin — the `web` guard (users table). Mounted under /admin by bootstrap/app.php.

// Guest — only reachable when NOT logged in.
Route::middleware('guest:web')->group(function () {
    Route::get('/login', fn () => Inertia::render('admin/login/page'))->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/forgot-password', [PasswordController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'sendResetLink'])
        ->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'resetPassword'])->name('password.update');
});

// Authenticated — pages render React only and load their data from /api/*.
Route::middleware('auth:sanctum')->group(function () {
    // Guests fall through to /admin/login via the redirect in bootstrap/app.php.
    Route::get('/', fn () => redirect()->route('dashboard'))->name('admin.index');

    Route::get('/dashboard', fn () => Inertia::render('admin/dashboard/page'))->name('dashboard');
    Route::get('/profile', fn () => Inertia::render('admin/profile/page'))->name('profile');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // User & role management — manage access only.
    Route::controller(UserPageController::class)
        ->prefix('users')
        ->name('users.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::get('/{user}/edit', 'edit')->name('edit');
        });

    Route::post('/users/{user}/impersonate', [ImpersonationController::class, 'start'])
        ->name('users.impersonate');

    Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])
        ->name('impersonate.stop');

    Route::controller(RolePageController::class)
        ->prefix('roles')
        ->name('roles.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::get('/{role}/edit', 'edit')->name('edit');
        });

    // Settings — forwards to the first section the user may open.
    Route::get('/settings', function () {
        $sections = [
            'view activity logs' => '/admin/activity-logs',
        ];

        foreach ($sections as $permission => $path) {
            if (Gate::allows($permission)) {
                return redirect($path);
            }
        }

        abort(403);
    })->name('settings.index');

    // Single list page — create and edit happen in a modal.
    Route::get('/activity-logs', [ActivityLogPageController::class, 'index'])->name('activity-logs.index');
});
