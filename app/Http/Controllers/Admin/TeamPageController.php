<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class TeamPageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view teams', only: ['index', 'show']),
            new Middleware('permission:create teams', only: ['create']),
            new Middleware('permission:edit teams', only: ['edit']),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('admin/teams/index/page');
    }

    public function create(): Response
    {
        return Inertia::render('admin/teams/create/page');
    }

    public function edit(string $team): Response
    {
        return Inertia::render('admin/teams/edit/page', [
            'teamId' => $team,
        ]);
    }

    public function show(string $team): Response
    {
        return Inertia::render('admin/teams/show/page', [
            'teamId' => $team,
        ]);
    }
}
