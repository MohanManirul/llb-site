<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\ClientPasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClientPasswordController extends Controller
{
    public function __construct(private readonly ClientPasswordResetService $passwords) {}

    public function showForgotForm(): Response
    {
        return Inertia::render('client/forgot-password/page');
    }

    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        if (! $this->passwords->sendResetLink($request->string('email')->toString())) {
            throw ValidationException::withMessages([
                'email' => ClientPasswordResetService::ACCOUNT_NOT_FOUND_MESSAGE,
            ]);
        }

        return back()->with('success', ClientPasswordResetService::LINK_SENT_MESSAGE);
    }

    public function showResetForm(Request $request, string $token): Response
    {
        return Inertia::render('client/reset-password/page', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        if (! $this->passwords->reset($request->validated())) {
            throw ValidationException::withMessages([
                'email' => ClientPasswordResetService::INVALID_TOKEN_MESSAGE,
            ]);
        }

        return redirect()->route('client.login')->with('success', ClientPasswordResetService::RESET_MESSAGE);
    }
}
