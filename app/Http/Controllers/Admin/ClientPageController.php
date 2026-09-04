<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class ClientPageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view clients', only: ['index']),
            new Middleware('permission:create clients', only: ['create']),
            new Middleware('permission:edit clients', only: ['edit']),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('admin/clients/index/page');
    }

    public function create(): Response
    {
        return Inertia::render('admin/clients/create/page');
    }

    public function edit(string $client): Response
    {
        return Inertia::render('admin/clients/edit/page', [
            'clientId' => $client,
        ]);
    }
}
