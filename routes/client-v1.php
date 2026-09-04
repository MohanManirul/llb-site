<?php

use App\Http\Controllers\V1\Admin\Projects\PaymentController;
use App\Http\Controllers\V1\Client\Auth\ClientAuthController;
use App\Http\Controllers\V1\Client\Dashboard\DashboardController;
use App\Http\Controllers\V1\Client\Dashboard\DashboardReportController;
use App\Http\Controllers\V1\Client\Notification\NotificationController;
use App\Http\Controllers\V1\Client\Projects\ProjectController;
use App\Http\Middleware\EnsureClientAudience;
use Illuminate\Support\Facades\Route;

Route::post('/client/login', [ClientAuthController::class, 'login']);

Route::middleware(['auth:client,client-web', EnsureClientAudience::class])
    ->prefix('client')
    ->name('client.')
    ->group(function () {
        Route::controller(ClientAuthController::class)->group(function () {
            Route::get('/me', 'me')->name('me');
            Route::post('/logout', 'logout')->name('logout');
        });

        Route::controller(NotificationController::class)
            ->prefix('notifications')
            ->name('notifications.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::patch('/read-all', 'markAllAsRead')->name('read-all');
                Route::patch('/{notification}/read', 'markAsRead')->name('read');
                Route::delete('/{notification}', 'destroy')->name('destroy');
            });

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/report', [DashboardReportController::class, 'index'])
            ->name('dashboard.report');

        Route::controller(ProjectController::class)
            ->prefix('projects')
            ->name('projects.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{project}', 'show')->name('show');

                Route::controller(PaymentController::class)->group(function () {
                    Route::get('{project}/payments/status', 'status')->name('payments.status');
                    Route::get('{project}/payments/history', 'history')->name('payments.history');
                });
            });
    });
