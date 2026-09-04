<?php

use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\ClientPasswordController;
use App\Http\Controllers\Client\PortalPageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('admin/welcome/page'));

Route::middleware('guest:client-web')
    ->name('client.')
    ->group(function () {
        Route::controller(ClientAuthController::class)
            ->group(function () {
                Route::get('/login', 'showLogin')->name('login');
                Route::post('/login', 'login')->name('login.store');
            });

        Route::controller(ClientPasswordController::class)
            ->name('password.')
            ->group(function () {
                Route::get('/forgot-password', 'showForgotForm')->name('request');
                Route::post('/forgot-password', 'sendResetLink')->name('email')->middleware('throttle:3,1');
                Route::get('/reset-password/{token}', 'showResetForm')->name('reset');
                Route::post('/reset-password', 'resetPassword')->name('update');
            });
    });

Route::middleware('auth:client-web')
    ->name('client.')
    ->group(function () {
        Route::controller(PortalPageController::class)->group(function () {
            Route::get('/dashboard', 'dashboard')->name('dashboard');
            Route::get('/profile', 'profile')->name('profile');
            Route::get('/projects', 'projects')->name('projects.index');
            Route::get('/projects/{project}', 'showProject')
                ->whereNumber('project')
                ->name('projects.show');
        });

        Route::post('/logout', [ClientAuthController::class, 'logout'])->name('logout');
    });
