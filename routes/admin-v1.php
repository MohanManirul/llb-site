<?php

use App\Http\Controllers\V1\Admin\Access\AccessController;
use App\Http\Controllers\V1\Admin\ActivityLog\ActivityLogController;
use App\Http\Controllers\V1\Admin\Auth\AuthController;
use App\Http\Controllers\V1\Admin\Dashboard\DashboardController;
use App\Http\Controllers\V1\Admin\Dashboard\DashboardReportController;
use App\Http\Controllers\V1\Admin\Notification\NotificationController;
use App\Http\Controllers\V1\Admin\Profile\ProfileController;
use App\Http\Controllers\V1\Admin\Role\RoleController;
use App\Http\Controllers\V1\Admin\User\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/report', [DashboardReportController::class, 'index'])
            ->name('dashboard.report');

        Route::controller(AuthController::class)->group(function () {
            Route::get('/me', 'me');
            Route::post('/logout', 'logout');
        });

        // User picker — must stay above apiResource('users').
        Route::get('users/search', [UserController::class, 'search'])
            ->name('users.search');

        // Login provisioning.
        Route::controller(AccessController::class)->group(function () {
            Route::post('staff', 'storeStaff')->name('staff.store');

            Route::get('permissions', 'permissions')->name('permissions.index');
        });

        // Users & roles — option lists must stay above the apiResource.
        Route::get('users/roles', [UserController::class, 'roleOptions'])
            ->name('users.roles');
        Route::apiResource('users', UserController::class);

        Route::get('roles/permission-groups', [RoleController::class, 'permissionGroups'])
            ->name('roles.permission-groups');
        Route::apiResource('roles', RoleController::class);

        // Select/autocomplete option lists — must stay above their apiResource.
        Route::get('activity-logs/filters', [ActivityLogController::class, 'filterOptions'])
            ->name('activity-logs.filters');

        // CRM resources.

        Route::apiResource('activity-logs', ActivityLogController::class)
            ->parameters(['activity-logs' => 'activityLog'])
            ->only(['index', 'destroy']);
    });

// Shared endpoints — must stay below the staff group.
Route::middleware('auth:sanctum')->group(function () {
    Route::controller(ProfileController::class)
        ->prefix('profile')
        ->name('profile.')
        ->group(function () {
            Route::get('/', 'show')->name('show');
            Route::patch('/', 'update')->name('update');
        });

    // Notifications.
    Route::controller(NotificationController::class)
        ->prefix('notifications')
        ->name('notifications.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::patch('/read-all', 'markAllAsRead')->name('read-all');
            Route::patch('/{notification}/read', 'markAsRead')->name('read');
            Route::delete('/{notification}', 'destroy')->name('destroy');
        });
});
