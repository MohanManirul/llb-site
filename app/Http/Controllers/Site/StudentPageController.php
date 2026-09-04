<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class StudentPageController extends Controller
{
    public function login(): Response
    {
        return Inertia::render('public/account/login/page');
    }

    public function register(): Response
    {
        return Inertia::render('public/account/register/page');
    }

    public function forgotPassword(): Response
    {
        return Inertia::render('public/account/forgot-password/page');
    }

    public function resetPassword(string $locale, string $token): Response
    {
        return Inertia::render('public/account/reset-password/page', [
            'token' => $token,
            'email' => request()->query('email', ''),
        ]);
    }

    public function profile(): Response
    {
        return Inertia::render('public/account/profile/page');
    }

    public function attempts(): Response
    {
        return Inertia::render('public/account/attempts/index/page');
    }

    public function attempt(string $locale, string $attempt): Response
    {
        return Inertia::render('public/account/attempts/show/page', [
            'attemptId' => $attempt,
        ]);
    }

    public function practice(): Response
    {
        return Inertia::render('public/practice/index/page');
    }

    public function practiceRun(): Response
    {
        return Inertia::render('public/practice/run/page');
    }

    public function attemptRunner(string $locale, string $modelTest, string $attempt): Response
    {
        return Inertia::render('public/model-tests/runner/page', [
            'modelTestSlug' => $modelTest,
            'attemptId' => $attempt,
        ]);
    }
}
