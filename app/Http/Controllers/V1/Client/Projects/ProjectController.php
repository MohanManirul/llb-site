<?php

namespace App\Http\Controllers\V1\Client\Projects;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\Projects\IndexClientProjectRequest;
use App\Http\Resources\Project\ProjectIndexResource;
use App\Http\Resources\Project\ProjectResource;
use App\Models\Client;
use App\Models\Project;
use App\Services\Project\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    public function index(IndexClientProjectRequest $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();

        $result = $this->projectService->paginateForClient($request->filters(), $client);

        request()->attributes->set('project_employee_ids', $result->employeeIds);
        request()->attributes->set('project_led_team_ids', $result->ledTeamIds);

        return ApiResponse::respondWithResourceCollection(
            ProjectIndexResource::collection($result->projects),
            'Projects retrieved successfully.',
        );
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();

        abort_unless($project->client_id === $client->id, 403, 'You can only view your own projects.');

        return ApiResponse::respondWithResource(
            new ProjectResource($project->load([
                'company:id,name',
                'department:id,name',
                'client:id,name,email,phone',
                'team:id,name',
                'assignedEmployee:id,user_id',
                'assignedEmployee.user:id,name,email,phone',
                'assignedEmployee.designation:id,name',
                'milestones',
                'sales' => fn ($query) => $query->latest('sale_date')->limit(25),
                'sales.employee:id,user_id',
                'sales.employee.user:id,name',
                'payments' => fn ($query) => $query->latest('payment_date')->limit(50),
                'payments.createdBy:id,name',
                'invoices' => fn ($query) => $query->latest('invoice_date')->limit(50),
            ])),
            'Project retrieved successfully.',
        );
    }
}
