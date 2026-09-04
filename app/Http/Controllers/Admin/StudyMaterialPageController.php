<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class StudyMaterialPageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view study materials', only: ['index']),
            new Middleware('permission:create study materials', only: ['create']),
            new Middleware('permission:edit study materials', only: ['edit']),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('admin/materials/index/page');
    }

    public function create(): Response
    {
        return Inertia::render('admin/materials/create/page');
    }

    public function edit(string $studyMaterial): Response
    {
        return Inertia::render('admin/materials/edit/page', [
            'materialId' => $studyMaterial,
        ]);
    }
}
