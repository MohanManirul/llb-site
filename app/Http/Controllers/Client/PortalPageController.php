<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PortalPageController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('client/dashboard/page');
    }

    public function profile(): Response
    {
        return Inertia::render('client/profile/page');
    }

    public function projects(): Response
    {
        return Inertia::render('client/projects/index/page');
    }

    public function showProject(string $project): Response
    {
        return Inertia::render('client/projects/show/page', [
            'projectId' => $project,
        ]);
    }
}
