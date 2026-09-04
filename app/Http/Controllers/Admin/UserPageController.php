<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class UserPageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view users', only: ['index']),
            new Middleware('permission:create users', only: ['create']),
            new Middleware('permission:edit users', only: ['edit']),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('admin/users/index/page');
    }

    public function create(): Response
    {
        return Inertia::render('admin/users/create/page');
    }

    public function edit(string $user): Response
    {
        return Inertia::render('admin/users/edit/page', [
            'userId' => $user,
        ]);
    }
}
