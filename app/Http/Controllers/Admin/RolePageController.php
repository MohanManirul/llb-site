<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class RolePageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view roles', only: ['index']),
            new Middleware('permission:create roles', only: ['create']),
            new Middleware('permission:edit roles', only: ['edit']),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('admin/roles/index/page');
    }

    public function create(): Response
    {
        return Inertia::render('admin/roles/create/page');
    }

    public function edit(string $role): Response
    {
        return Inertia::render('admin/roles/edit/page', [
            'roleId' => $role,
        ]);
    }
}
