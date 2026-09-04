<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class NoticePageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view notices', only: ['index']),
            new Middleware('permission:create notices', only: ['create']),
            new Middleware('permission:edit notices', only: ['edit']),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('admin/notices/index/page');
    }

    public function create(): Response
    {
        return Inertia::render('admin/notices/create/page');
    }

    public function edit(string $notice): Response
    {
        return Inertia::render('admin/notices/edit/page', [
            'noticeId' => $notice,
        ]);
    }
}
