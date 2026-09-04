<?php

namespace App\Http\Controllers\V1\Admin\Department;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Department\IndexDepartmentRequest;
use App\Http\Requests\V1\Admin\Department\StoreDepartmentRequest;
use App\Http\Requests\V1\Admin\Department\UpdateDepartmentRequest;
use App\Http\Resources\Department\DepartmentResource;
use App\Models\Department;
use App\Services\Department\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view departments', only: ['index', 'show', 'search']),
            new Middleware('permission:create departments', only: ['store']),
            new Middleware('permission:edit departments', only: ['update']),
            new Middleware('permission:delete departments', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly DepartmentService $departmentService,
    ) {}

    public function index(IndexDepartmentRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            DepartmentResource::collection($this->departmentService->paginate($request->filters())),
            'Departments retrieved successfully.',
        );
    }

    public function search(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->departmentService->searchOptions(
                $request->input('search'),
                $request->input('company_id'),
            ),
            'Departments retrieved successfully.',
        );
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = DB::transaction(function () use ($request) {
            $department = $this->departmentService->create($request->validated());

            activity()->performedOn($department)->log('Department created.');

            return $department;
        });

        return ApiResponse::respondWithResource(
            new DepartmentResource($department->load('company')),
            'Department created successfully.',
            201,
        );
    }

    public function show(Department $department): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new DepartmentResource($department->load('company')),
            'Department retrieved successfully.',
        );
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department = DB::transaction(function () use ($request, $department) {
            $department = $this->departmentService->update($department, $request->validated());

            activity()->performedOn($department)->log('Department updated.');

            return $department;
        });

        return ApiResponse::respondWithResource(
            new DepartmentResource($department->load('company')),
            'Department updated successfully.',
        );
    }

    public function destroy(Department $department): JsonResponse
    {
        DB::transaction(function () use ($department) {
            $this->departmentService->delete($department);

            activity()->performedOn($department)->log('Department deleted.');
        });

        return ApiResponse::respondSuccess('Department deleted successfully.');
    }
}
