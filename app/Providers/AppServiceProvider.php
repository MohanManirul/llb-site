<?php

namespace App\Providers;

use App\Models\User;
use App\Notifications\Channels\DatabaseChannel;
use App\Services\Auth\ImpersonationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        // Named limiters keep separate hit counters; two inline throttle
        // definitions on nested groups would double-count every request.
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('downloads', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        Gate::define('viewLogViewer', fn (User $user) => $user->can('view system monitoring'));
    }
}
