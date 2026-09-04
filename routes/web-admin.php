<?php

use App\Http\Controllers\Admin\ActivityLogPageController;
use App\Http\Controllers\Admin\ClientPageController;
use App\Http\Controllers\Admin\CompanyPageController;
use App\Http\Controllers\Admin\DepartmentPageController;
use App\Http\Controllers\Admin\DesignationPageController;
use App\Http\Controllers\Admin\EmployeePageController;
use App\Http\Controllers\Admin\ProjectPageController;
use App\Http\Controllers\Admin\RolePageController;
use App\Http\Controllers\Admin\TeamPageController;
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
            'view companies' => '/admin/companies',
            'view departments' => '/admin/departments',
            'view designations' => '/admin/designations',
            'view activity logs' => '/admin/activity-logs',
        ];

        foreach ($sections as $permission => $path) {
            if (Gate::allows($permission)) {
                return redirect($path);
            }
        }

        abort(403);
    })->name('settings.index');

    // Single list pages — create and edit happen in a modal.
    Route::get('/companies', [CompanyPageController::class, 'index'])->name('companies.index');

    Route::get('/departments', [DepartmentPageController::class, 'index'])->name('departments.index');

    Route::get('/designations', [DesignationPageController::class, 'index'])->name('designations.index');

    Route::get('/activity-logs', [ActivityLogPageController::class, 'index'])->name('activity-logs.index');

    Route::controller(EmployeePageController::class)
        ->prefix('employees')
        ->name('employees.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::get('/{employee}/edit', 'edit')->name('edit');
        });

    Route::controller(TeamPageController::class)
        ->prefix('teams')
        ->name('teams.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::get('/{team}/edit', 'edit')->name('edit');
            Route::get('/{team}', 'show')->name('show');
        });

    Route::controller(ClientPageController::class)
        ->prefix('clients')
        ->name('clients.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::get('/{client}/edit', 'edit')->name('edit');
        });

    // Show carries no permission — the API scopes what each role may load.
    Route::controller(ProjectPageController::class)
        ->prefix('projects')
        ->name('projects.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::get('/{project}/edit', 'edit')->name('edit');
            Route::get('/{project}/reports', 'reports')->name('reports');
            Route::get('/{project}', 'show')->name('show');
        });
});
