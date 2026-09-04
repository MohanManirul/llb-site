<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class SubjectPageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view academic structure', only: ['index']),
            new Middleware('permission:create academic structure', only: ['create']),
            new Middleware('permission:edit academic structure', only: ['edit']),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('admin/subjects/index/page');
    }

    public function create(): Response
    {
        return Inertia::render('admin/subjects/create/page');
    }

    public function edit(string $subject): Response
    {
        return Inertia::render('admin/subjects/edit/page', [
            'subjectId' => $subject,
        ]);
    }
}
