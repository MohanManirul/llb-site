<?php

namespace App\Providers;

use App\Models\User;
use App\Notifications\Channels\DatabaseChannel;
use App\Services\Auth\ImpersonationService;
use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BaseDatabaseChannel::class, DatabaseChannel::class);
        $this->app->singleton(ImpersonationService::class);
    }

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        Gate::define('viewLogViewer', fn (User $user) => $user->can('view system monitoring'));
    }
}
