<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\ClientAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientAuthController extends Controller
{
    public function __construct(
        private readonly ClientAuthService $clientAuthService,
    ) {}

    public function showLogin(): Response
    {
        return Inertia::render('client/login/page');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $client = $this->clientAuthService->attemptSession(
            $request->validated(),
            $request->boolean('remember'),
        );

        $request->session()->regenerate();

        activity()->performedOn($client)->log('Client signed in.');

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        activity()->performedOn($request->user())->log('Client signed out.');

        $this->clientAuthService->logoutSession();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
