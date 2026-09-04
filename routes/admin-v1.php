<?php

use App\Http\Controllers\V1\Admin\Access\AccessController;
use App\Http\Controllers\V1\Admin\ActivityLog\ActivityLogController;
use App\Http\Controllers\V1\Admin\Auth\AuthController;
use App\Http\Controllers\V1\Admin\CallCenter\AgentController;
use App\Http\Controllers\V1\Admin\CallCenter\AgentPerformanceController;
use App\Http\Controllers\V1\Admin\CallCenter\AgentReportController;
use App\Http\Controllers\V1\Admin\CallCenter\AgentSearchController;
use App\Http\Controllers\V1\Admin\CallCenter\BusinessSearchController;
use App\Http\Controllers\V1\Admin\CallCenter\CourierScoreController;
use App\Http\Controllers\V1\Admin\CallCenter\FilterOptionController;
use App\Http\Controllers\V1\Admin\CallCenter\IncompleteOrderController;
use App\Http\Controllers\V1\Admin\CallCenter\NewOrderController;
use App\Http\Controllers\V1\Admin\CallCenter\OrderCommentController;
use App\Http\Controllers\V1\Admin\CallCenter\OrderCustomerController;
use App\Http\Controllers\V1\Admin\CallCenter\OrderItemController;
use App\Http\Controllers\V1\Admin\CallCenter\OrderLookupController;
use App\Http\Controllers\V1\Admin\CallCenter\OrderMoneyController;
use App\Http\Controllers\V1\Admin\CallCenter\OrderStatusController;
use App\Http\Controllers\V1\Admin\CallCenter\OverviewController;
use App\Http\Controllers\V1\Admin\CallCenter\PickedOrderController;
use App\Http\Controllers\V1\Admin\CallCenter\ProductSearchController;
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

        // Call center. The {order} binding resolves against the boneek
        // database and is scoped in AppServiceProvider.
        Route::prefix('call-center')->name('call-center.')->group(function () {
            Route::get('overview', OverviewController::class)->name('overview');
            Route::get('new-orders', [NewOrderController::class, 'index'])->name('new-orders');
            Route::get('picked-orders', [PickedOrderController::class, 'index'])->name('picked-orders');
            Route::get('picked-orders/stats', [PickedOrderController::class, 'stats'])
                ->name('picked-orders.stats');

            Route::get('filter-options', FilterOptionController::class)->name('filter-options');
            Route::get('courier-score', CourierScoreController::class)->name('courier-score');
            Route::get('businesses/search', BusinessSearchController::class)->name('businesses.search');
            Route::get('agents/search', AgentSearchController::class)->name('agents.search');
            Route::get('agent-performance', AgentPerformanceController::class)->name('agent-performance');

            Route::get('agent-performance/stats', [AgentPerformanceController::class, 'stats'])
                ->name('agent-performance.stats');

            /*
             * The Performance menu's read side. Nothing under it writes, and
             * it is deliberately a separate door from the agent's own picked
             * orders: that one shuts once an approval releases the pick, and
             * a report has to stay readable after the work is finished.
             */
            Route::prefix('agent-performance/{user}')->name('agent-performance.')->group(function () {
                Route::get('/', [AgentReportController::class, 'show'])->name('show');
                Route::get('orders', [AgentReportController::class, 'orders'])->name('orders');
                Route::get('orders/{order}', [AgentReportController::class, 'order'])->name('orders.show');
                Route::get('orders/{order}/history', [AgentReportController::class, 'orderHistory'])
                    ->name('orders.history');
            });

            // Must stay above the {order} wildcard or "bulk-pick" is read as an id.
            Route::post('orders/bulk-pick', [NewOrderController::class, 'bulkPick'])->name('orders.bulk-pick');
            Route::post('orders/bulk-unpick', [PickedOrderController::class, 'bulkUnpick'])
                ->name('orders.bulk-unpick');

            Route::get('orders/{order}', [PickedOrderController::class, 'show'])->name('orders.show');
            Route::get('orders/{order}/history', [PickedOrderController::class, 'history'])->name('orders.history');
            Route::post('orders/{order}/pick', [NewOrderController::class, 'pick'])->name('orders.pick');
            Route::delete('orders/{order}/pick', [PickedOrderController::class, 'unpick'])->name('orders.unpick');
            Route::patch('orders/{order}/status', OrderStatusController::class)->name('orders.status');

            /*
             * Editing one order. Every route below writes to the merchant's own
             * database, so each controller runs OrderEditAccess before it does:
             * the permission alone is not enough, the agent has to be holding
             * the order.
             *
             * scopeBindings ties {orderItem} to {order} — without it, an item id
             * from a different order would resolve and be edited.
             */
            Route::prefix('orders/{order}')->name('orders.')->scopeBindings()->group(function () {
                Route::get('lookups', OrderLookupController::class)->name('lookups');
                Route::get('products/search', ProductSearchController::class)->name('products.search');

                Route::post('items', [OrderItemController::class, 'store'])->name('items.store');
                Route::patch('items/{orderItem}/quantity', [OrderItemController::class, 'updateQuantity'])
                    ->name('items.quantity');
                Route::patch('items/{orderItem}/discount', [OrderItemController::class, 'updateDiscount'])
                    ->name('items.discount');
                Route::delete('items/{orderItem}', [OrderItemController::class, 'destroy'])
                    ->name('items.destroy');

                Route::patch('discount', [OrderMoneyController::class, 'updateDiscount'])
                    ->name('discount');
                Route::patch('shipping-charge', [OrderMoneyController::class, 'updateShippingCharge'])
                    ->name('shipping-charge');

                Route::patch('shipping-address', [OrderCustomerController::class, 'updateShippingAddress'])
                    ->name('shipping-address');
                Route::patch('customer', [OrderCustomerController::class, 'updateCustomer'])
                    ->name('customer');
                Route::patch('note', [OrderCustomerController::class, 'updateNote'])->name('note');

                /*
                 * The CRM's own timeline. Deliberately not under the
                 * `edit call center orders` permission the routes above carry —
                 * a comment writes nothing to the merchant's database.
                 */
                Route::post('comments', [OrderCommentController::class, 'store'])->name('comments');
            });

            Route::get('locations/{level}', [OrderLookupController::class, 'locations'])
                ->whereIn('level', ['divisions', 'districts', 'areas'])
                ->name('locations');

            // Must stay above the apiResource or {agent} swallows "candidates".
            Route::get('agents/candidates', [AgentController::class, 'candidates'])
                ->name('agents.candidates');

            Route::apiResource('agents', AgentController::class)
                ->only(['index', 'store', 'update', 'destroy']);
        });

        /*
         * Orders. Its own menu, outside the call center's: an abandoned
         * checkout is not an order an agent holds. Read-only — the row is
         * inventory's and its whole lifecycle stays there.
         */
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('incomplete-orders', [IncompleteOrderController::class, 'index'])
                ->name('incomplete-orders');
        });

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
