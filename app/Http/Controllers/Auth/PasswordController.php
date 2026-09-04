<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwords) {}

    public function showForgotForm(): Response
    {
        return Inertia::render('admin/forgot-password/page');
    }

    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        if (! $this->passwords->sendResetLink($request->string('email')->toString())) {
            throw ValidationException::withMessages([
                'email' => PasswordResetService::ACCOUNT_NOT_FOUND_MESSAGE,
            ]);
        }

        return back()->with('success', PasswordResetService::LINK_SENT_MESSAGE);
    }

    public function showResetForm(Request $request, string $token): Response
    {
        return Inertia::render('admin/reset-password/page', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        if (! $this->passwords->reset($request->validated())) {
            throw ValidationException::withMessages([
                'email' => PasswordResetService::INVALID_TOKEN_MESSAGE,
            ]);
        }

        return redirect()->route('login')->with('success', PasswordResetService::RESET_MESSAGE);
    }
}
