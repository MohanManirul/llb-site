<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class CollegePageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view colleges', only: ['index']),
            new Middleware('permission:create colleges', only: ['create']),
            new Middleware('permission:edit colleges', only: ['edit']),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('admin/colleges/index/page');
    }

    public function create(): Response
    {
        return Inertia::render('admin/colleges/create/page');
    }

    public function edit(string $college): Response
    {
        return Inertia::render('admin/colleges/edit/page', [
            'collegeId' => $college,
        ]);
    }
}
