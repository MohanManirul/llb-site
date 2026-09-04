<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class EmployeePageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view employees', only: ['index']),
            new Middleware('permission:create employees', only: ['create']),
            new Middleware('permission:edit employees', only: ['edit']),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('admin/employees/index/page');
    }

    public function create(): Response
    {
        return Inertia::render('admin/employees/create/page');
    }

    public function edit(string $employee): Response
    {
        return Inertia::render('admin/employees/edit/page', [
            'employeeId' => $employee,
        ]);
    }
}
