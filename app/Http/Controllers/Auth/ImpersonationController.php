<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ImpersonationController extends Controller implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:impersonate users', only: ['start']),
        ];
    }

    public function __construct(
        private readonly ImpersonationService $impersonation,
    ) {}

    public function start(Request $request, User $user): RedirectResponse
    {
        $this->impersonation->start($request->user(), $user);

        return redirect()->to('/admin/dashboard');
    }

    public function stop(): RedirectResponse
    {
        if (! $this->impersonation->stop()) {
            return redirect('/admin/login');
        }

        return redirect('/admin/dashboard');
    }
}
