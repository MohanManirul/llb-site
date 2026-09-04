<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function login(LoginRequest $request): RedirectResponse
    {
        $account = $this->authService->attemptSession(
            $request->validated(),
            $request->boolean('remember'),
        );

        $request->session()->regenerate();

        activity()->performedOn($account)->log('Signed in.');

        return redirect()->intended('/admin/dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        activity()->performedOn($request->user())->log('Signed out.');

        $this->authService->logoutSession();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
