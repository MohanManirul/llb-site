<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class QuestionPageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view questions', only: ['index']),
            new Middleware('permission:create questions', only: ['create', 'import']),
            new Middleware('permission:edit questions', only: ['edit']),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('admin/questions/index/page');
    }

    public function create(): Response
    {
        return Inertia::render('admin/questions/create/page');
    }

    public function edit(string $question): Response
    {
        return Inertia::render('admin/questions/edit/page', [
            'questionId' => $question,
        ]);
    }

    public function import(): Response
    {
        return Inertia::render('admin/questions/import/page');
    }
}
