<?php

namespace App\Http\Controllers\V1\Admin\Employees;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Employees\IndexEmployeeRequest;
use App\Http\Requests\V1\Admin\Employees\StoreEmployeeRequest;
use App\Http\Requests\V1\Admin\Employees\UpdateEmployeeRequest;
use App\Http\Resources\Employee\EmployeeResource;
use App\Models\Employee;
use App\Services\Employee\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class EmployeeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view employees', only: ['index', 'show', 'search']),
            new Middleware('permission:create employees', only: ['store']),
            new Middleware('permission:edit employees', only: ['update']),
            new Middleware('permission:delete employees', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly EmployeeService $employeeService,
    ) {}

    public function index(IndexEmployeeRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            EmployeeResource::collection($this->employeeService->paginate($request->filters())),
            'Employees retrieved successfully.',
        );
    }

    public function search(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->employeeService->searchOptions(
                $request->integer('company_id') ?: null,
                $request->input('search'),
                $request->integer('department_id') ?: null,
            ),
            'Employees retrieved successfully.',
        );
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->employeeService->create($request->validated());

        activity()->performedOn($employee)->log('Employee created.');

        return ApiResponse::respondWithResource(
            new EmployeeResource($employee->load(['user', 'company', 'department', 'designation'])),
            'Employee created successfully.',
            201,
        );
    }

    public function show(Employee $employee): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new EmployeeResource($employee->load(['user', 'company', 'department', 'designation'])),
            'Employee retrieved successfully.',
        );
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee = $this->employeeService->update($employee, $request->validated());

        activity()->performedOn($employee)->log('Employee updated.');

        return ApiResponse::respondWithResource(
            new EmployeeResource($employee->load(['user', 'company', 'department', 'designation'])),
            'Employee updated successfully.',
        );
    }

    public function destroy(Employee $employee): JsonResponse
    {
        activity()->performedOn($employee)->log('Employee deleted.');

        $this->employeeService->delete($employee);

        return ApiResponse::respondSuccess('Employee deleted successfully.');
    }
}
