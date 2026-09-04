<?php

namespace App\Http\Controllers\V1\Admin\Role;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Role\IndexRoleRequest;
use App\Http\Requests\V1\Admin\Role\StoreRoleRequest;
use App\Http\Requests\V1\Admin\Role\UpdateRoleRequest;
use App\Http\Resources\Role\RoleResource;
use App\Services\Role\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view roles', only: ['index', 'show', 'permissionGroups']),
            new Middleware('permission:create roles', only: ['store']),
            new Middleware('permission:edit roles', only: ['update']),
            new Middleware('permission:delete roles', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    public function index(IndexRoleRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            RoleResource::collection($this->roleService->paginate($request->filters())),
            'Roles retrieved successfully.',
        );
    }

    public function permissionGroups(): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->roleService->permissionGroups(),
            'Permission groups retrieved successfully.',
        );
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->create($request->validated());

        activity()->performedOn($role)->log('Role created.');

        return ApiResponse::respondWithResource(
            new RoleResource($role),
            'Role created successfully.',
            201,
        );
    }

    public function show(Role $role): JsonResponse
    {
        if ($role->name === 'super-admin') {
            return ApiResponse::respondForbidden('The super-admin role cannot be edited.');
        }

        return ApiResponse::respondWithResource(
            new RoleResource($role->load('permissions')),
            'Role retrieved successfully.',
        );
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        if ($role->name === 'super-admin') {
            return ApiResponse::respondForbidden('The super-admin role cannot be edited.');
        }

        $role = $this->roleService->update($role, $request->validated());

        activity()->performedOn($role)->log('Role updated.');

        return ApiResponse::respondWithResource(
            new RoleResource($role),
            'Role updated successfully.',
        );
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->name === 'super-admin') {
            return ApiResponse::respondForbidden('The super-admin role cannot be deleted.');
        }

        activity()->performedOn($role)->log('Role deleted.');

        $this->roleService->delete($role);

        return ApiResponse::respondSuccess('Role deleted successfully.');
    }
}
