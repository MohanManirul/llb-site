<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Traits\ChecksProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class ProjectPageController extends Controller implements HasMiddleware
{
    use ChecksProjectAccess;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:create projects', only: ['create']),
            new Middleware('permission:edit projects', only: ['edit']),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('admin/projects/index/page');
    }

    public function create(): Response
    {
        return Inertia::render('admin/projects/create/page');
    }

    public function edit(Request $request, Project $project): Response
    {
        $this->ensureCanEditProject($request->user(), $project);

        return Inertia::render('admin/projects/edit/page', [
            'projectId' => (string) $project->id,
        ]);
    }

    public function reports(Request $request, Project $project): Response
    {
        $this->ensureCanViewProjectReports($request->user(), $project);

        return Inertia::render('admin/projects/reports/page', [
            'projectId' => (string) $project->id,
            'canSubmitReports' => $this->canSubmitProjectReports($request->user(), $project),
        ]);
    }

    public function show(string $project): Response
    {
        $paymentTypes = array_map(
            fn (PaymentTypeEnum $enum) => [
                'value' => $enum->value,
                'label' => $enum->label(),
                'options' => $enum->options(),
            ],
            PaymentTypeEnum::cases()
        );

        return Inertia::render('admin/projects/show/page', [
            'projectId' => $project,
            'paymentTypes' => $paymentTypes,
        ]);
    }
}
