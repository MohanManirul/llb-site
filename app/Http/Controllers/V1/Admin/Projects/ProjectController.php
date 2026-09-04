<?php

namespace App\Http\Controllers\V1\Admin\Projects;

use App\Enums\BusinessStatus;
use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Projects\IndexProjectRequest;
use App\Http\Requests\V1\Admin\Projects\StoreProjectRequest;
use App\Http\Requests\V1\Admin\Projects\UpdateProjectBusinessStatusRequest;
use App\Http\Requests\V1\Admin\Projects\UpdateProjectRequest;
use App\Http\Resources\Project\ProjectIndexResource;
use App\Http\Resources\Project\ProjectResource;
use App\Models\Project;
use App\Services\Project\ProjectService;
use App\Traits\ChecksProjectAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProjectController extends Controller implements HasMiddleware
{
    use ChecksProjectAccess;

    public static function middleware(): array
    {
        return [
            new Middleware(
                'permission:view projects|create projects|edit projects',
                only: [
                    'searchTeams', 'searchEmployees', 'searchClients',
                    'searchCompanies', 'searchDepartments',
                ],
            ),
            new Middleware('permission:create projects', only: ['store']),
            new Middleware(
                'permission:edit projects',
                only: ['update', 'updateBusinessStatus'],
            ),
            new Middleware('permission:delete projects', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    private function detailRelations(): array
    {
        return [
            'company:id,name',
            'department:id,name',
            'client:id,name,email,phone',
            'team:id,name',
            'assignedEmployee:id,user_id,designation_id',
            'assignedEmployee.user:id,name,email,phone,image',
            'assignedEmployee.designation:id,name',
            'milestones',
            'sales' => fn ($query) => $query->latest('sale_date')->limit(25),
            'sales.employee:id,user_id',
            'sales.employee.user:id,name',
            'assignments.team:id,name',
            'assignments.employee:id,user_id',
            'assignments.employee.user:id,name',
            'payments' => fn ($query) => $query->latest('payment_date')->limit(50),
            'payments.createdBy:id,name',
            'invoices' => fn ($query) => $query->latest('invoice_date')->limit(50),
        ];
    }

    public function index(IndexProjectRequest $request): JsonResponse
    {
        $result = $this->projectService->paginateForUser($request->filters(), $request->user());

        request()->attributes->set('project_employee_ids', $result->employeeIds);
        request()->attributes->set('project_led_team_ids', $result->ledTeamIds);

        return ApiResponse::respondWithResourceCollection(
            ProjectIndexResource::collection($result->projects),
            'Projects retrieved successfully.',
        );
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create($request->validated());

        activity()->performedOn($project)->log('Project created.');

        return ApiResponse::respondWithResource(
            new ProjectResource($project->load($this->detailRelations())),
            'Project created successfully.',
            201,
        );
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->ensureCanAccessProject($request->user(), $project);

        return ApiResponse::respondWithResource(
            new ProjectResource($project->load($this->detailRelations())),
            'Project retrieved successfully.',
        );
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $project = $this->projectService->update($project, $request->validated());

        activity()->performedOn($project)->log('Project updated.');

        return ApiResponse::respondWithResource(
            new ProjectResource($project->load($this->detailRelations())),
            'Project updated successfully.',
        );
    }

    public function updateBusinessStatus(
        UpdateProjectBusinessStatusRequest $request,
        Project $project,
    ): JsonResponse {
        $project = $this->projectService->updateBusinessStatus(
            $project,
            BusinessStatus::from($request->string('business_status')->toString()),
        );

        activity()->performedOn($project)->log('Project business status updated.');

        return ApiResponse::respondWithResource(
            new ProjectResource($project),
            'Project business status updated successfully.',
        );
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->ensureCanDeleteProject($request->user(), $project);

        activity()->performedOn($project)->log('Project deleted.');

        $project->delete();

        return ApiResponse::respondSuccess('Project deleted successfully.');
    }

    public function searchTeams(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->projectService->searchTeamOptions(
                $request->integer('company_id') ?: null,
                $request->input('search'),
                $request->integer('department_id') ?: null,
            ),
            'Teams retrieved successfully.',
        );
    }

    public function searchEmployees(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->projectService->searchTeamMemberOptions(
                $request->integer('team_id') ?: null,
                $request->input('search'),
            ),
            'Employees retrieved successfully.',
        );
    }

    public function searchClients(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->projectService->searchClientOptions($request->input('search')),
            'Clients retrieved successfully.',
        );
    }

    public function searchCompanies(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->projectService->searchCompanyOptions($request->input('search')),
            'Companies retrieved successfully.',
        );
    }

    public function searchDepartments(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->projectService->searchDepartmentOptions(
                $request->input('search'),
                $request->integer('company_id') ?: null,
            ),
            'Departments retrieved successfully.',
        );
    }
}
