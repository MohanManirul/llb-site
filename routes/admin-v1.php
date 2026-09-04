<?php

use App\Http\Controllers\V1\Admin\Access\AccessController;
use App\Http\Controllers\V1\Admin\ActivityLog\ActivityLogController;
use App\Http\Controllers\V1\Admin\Auth\AuthController;
use App\Http\Controllers\V1\Admin\Client\ClientController;
use App\Http\Controllers\V1\Admin\Company\CompanyController;
use App\Http\Controllers\V1\Admin\Dashboard\DashboardController;
use App\Http\Controllers\V1\Admin\Dashboard\DashboardReportController;
use App\Http\Controllers\V1\Admin\Department\DepartmentController;
use App\Http\Controllers\V1\Admin\Designation\DesignationController;
use App\Http\Controllers\V1\Admin\Employees\EmployeeController;
use App\Http\Controllers\V1\Admin\Notification\NotificationController;
use App\Http\Controllers\V1\Admin\Profile\ProfileController;
use App\Http\Controllers\V1\Admin\Projects\PaymentController;
use App\Http\Controllers\V1\Admin\Projects\ProjectController;
use App\Http\Controllers\V1\Admin\Projects\ProjectNoteController;
use App\Http\Controllers\V1\Admin\Projects\ProjectSalesReportController;
use App\Http\Controllers\V1\Admin\Role\RoleController;
use App\Http\Controllers\V1\Admin\Teams\MyTeamController;
use App\Http\Controllers\V1\Admin\Teams\TeamController;
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
            Route::prefix('clients')
                ->name('clients.login-access.')
                ->group(function () {
                    Route::post('{client}/login-access', 'grantClientLogin')->name('grant');
                    Route::delete('{client}/login-access', 'revokeClientLogin')->name('revoke');
                });

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
        Route::get('companies/search', [CompanyController::class, 'search'])
            ->name('companies.search');

        Route::get('departments/search', [DepartmentController::class, 'search'])
            ->name('departments.search');

        Route::get('designations/search', [DesignationController::class, 'search'])
            ->name('designations.search');

        Route::get('employees/search', [EmployeeController::class, 'search'])
            ->name('employees.search');

        Route::controller(TeamController::class)
            ->prefix('teams')
            ->name('teams.')
            ->group(function () {
                Route::get('members/search', 'searchMembers')->name('members.search');
                Route::get('search', 'search')->name('search');
            });

        Route::controller(MyTeamController::class)
            ->prefix('my-teams')
            ->name('my-teams.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{team}', 'show')->name('show');
            });

        // Projects — list, detail, searches, inline status, notes, sales reports.
        Route::prefix('projects')
            ->name('projects.')
            ->group(function () {
                Route::controller(ProjectController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('teams/search', 'searchTeams')->name('teams.search');
                    Route::get('employees/search', 'searchEmployees')->name('employees.search');
                    Route::get('clients/search', 'searchClients')->name('clients.search');
                    Route::get('companies/search', 'searchCompanies')->name('companies.search');
                    Route::get('departments/search', 'searchDepartments')->name('departments.search');
                    Route::patch('{project}/business-status', 'updateBusinessStatus')
                        ->name('business-status.update');
                });

                Route::controller(ProjectNoteController::class)->group(function () {
                    Route::get('{project}/notes', 'index')->name('notes.index');
                    Route::post('{project}/notes', 'store')->name('notes.store');
                    Route::patch('{project}/notes/{note}', 'update')->name('notes.update');
                    Route::delete('{project}/notes/{note}', 'destroy')->name('notes.destroy');
                });

                Route::controller(ProjectSalesReportController::class)->group(function () {
                    Route::get('{project}/sales-reports', 'index')->name('sales-reports.index');
                    Route::post('{project}/sales-reports/import', 'import')->name('sales-reports.import');
                    Route::get('{project}/sales-reports/import/{importId}', 'importStatus')
                        ->name('sales-reports.import.status');
                    Route::post('{project}/sales-reports', 'store')->name('sales-reports.store');
                    Route::patch('{project}/sales-reports/{salesReport}', 'update')->name('sales-reports.update');
                    Route::delete('{project}/sales-reports/{salesReport}', 'destroy')->name('sales-reports.destroy');
                });

                Route::controller(PaymentController::class)->group(function () {
                    Route::post('{project}/payments', 'store')->name('payments.store');
                    Route::get('{project}/payments/status', 'status')->name('payments.status');
                    Route::get('{project}/payments/history', 'history')->name('payments.history');
                });

                // Last — {project} would otherwise swallow the search paths above.
                Route::get('{project}', [ProjectController::class, 'show'])
                    ->whereNumber('project')
                    ->name('show');
            });

        // Client CSV import — must stay above apiResource('clients').
        Route::post('clients/import', [ClientController::class, 'import'])
            ->name('clients.import');

        Route::get('activity-logs/filters', [ActivityLogController::class, 'filterOptions'])
            ->name('activity-logs.filters');

        // CRM resources.
        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('designations', DesignationController::class);
        Route::apiResource('employees', EmployeeController::class);
        Route::apiResource('teams', TeamController::class);
        Route::apiResource('clients', ClientController::class);
        Route::apiResource('projects', ProjectController::class)->except(['index', 'show']);

        Route::apiResource('activity-logs', ActivityLogController::class)
            ->parameters(['activity-logs' => 'activityLog'])
            ->only(['index', 'destroy']);
    });

// Shared with clients — must stay below the staff group.
Route::middleware('auth:sanctum,client,client-web')->group(function () {
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
