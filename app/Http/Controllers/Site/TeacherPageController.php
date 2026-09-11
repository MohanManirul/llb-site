<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TeacherPageController extends Controller
{
    public function login(): Response
    {
        return Inertia::render('public/teacher/login/page');
    }

    public function register(): Response
    {
        return Inertia::render('public/teacher/register/page');
    }

    public function registered(): Response
    {
        return Inertia::render('public/teacher/registered/page');
    }

    public function forgotPassword(): Response
    {
        return Inertia::render('public/teacher/forgot-password/page');
    }

    public function resetPassword(string $locale, string $token): Response
    {
        return Inertia::render('public/teacher/reset-password/page', [
            'token' => $token,
            'email' => request()->query('email', ''),
        ]);
    }

    public function profile(): Response
    {
        return Inertia::render('public/teacher/profile/page');
    }

    public function routines(): Response
    {
        return Inertia::render('public/teacher/routines/index/page');
    }

    public function createRoutine(): Response
    {
        return Inertia::render('public/teacher/routines/create/page');
    }

    public function editRoutine(string $locale, string $routine): Response
    {
        return Inertia::render('public/teacher/routines/edit/page', [
            'routineId' => $routine,
        ]);
    }

    public function notices(): Response
    {
        return Inertia::render('public/teacher/notices/index/page');
    }

    public function createNotice(): Response
    {
        return Inertia::render('public/teacher/notices/create/page');
    }

    public function editNotice(string $locale, string $notice): Response
    {
        return Inertia::render('public/teacher/notices/edit/page', [
            'noticeId' => $notice,
        ]);
    }

    public function notes(): Response
    {
        return Inertia::render('public/teacher/notes/index/page');
    }

    public function createNote(): Response
    {
        return Inertia::render('public/teacher/notes/create/page');
    }

    public function editNote(string $locale, string $note): Response
    {
        return Inertia::render('public/teacher/notes/edit/page', [
            'noteId' => $note,
        ]);
    }
}
