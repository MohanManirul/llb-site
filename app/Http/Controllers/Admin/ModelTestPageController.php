<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class ModelTestPageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view model tests', only: ['index']),
            new Middleware('permission:create model tests', only: ['create']),
            new Middleware('permission:edit model tests', only: ['edit']),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('admin/model-tests/index/page');
    }

    public function create(): Response
    {
        return Inertia::render('admin/model-tests/create/page');
    }

    public function edit(string $modelTest): Response
    {
        return Inertia::render('admin/model-tests/edit/page', [
            'modelTestId' => $modelTest,
        ]);
    }
}
