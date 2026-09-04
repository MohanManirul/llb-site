<?php

namespace App\Http\Controllers\V1\Admin\User;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\IndexUserRequest;
use App\Http\Requests\V1\Admin\User\StoreUserRequest;
use App\Http\Requests\V1\Admin\User\UpdateUserRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view users', only: ['search']),
            new Middleware('permission:view users', only: ['index', 'show', 'roleOptions']),
            new Middleware('permission:create users', only: ['store']),
            new Middleware('permission:edit users', only: ['update']),
            new Middleware('permission:delete users', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(IndexUserRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            UserResource::collection($this->userService->paginate($request->filters())),
            'Users retrieved successfully.',
        );
    }

    public function search(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->userService->searchOptions($request->input('search')),
            'Users retrieved successfully.',
        );
    }

    public function roleOptions(): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->userService->assignableRoles(),
            'Roles retrieved successfully.',
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create(
            $request->validated(),
            $this->storeFile($request, 'image'),
        );

        activity()->performedOn($user)->log('User created.');

        return ApiResponse::respondWithResource(
            new UserResource($user),
            'User created successfully.',
            201,
        );
    }

    public function show(User $user): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new UserResource($user),
            'User retrieved successfully.',
        );
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->update(
            $user,
            $request->validated(),
            $this->storeFile($request, 'image', $user->image, $request->boolean('remove_image')),
        );

        activity()->performedOn($user)->log('User updated.');

        return ApiResponse::respondWithResource(
            new UserResource($user),
            'User updated successfully.',
        );
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return ApiResponse::respondError('You cannot delete your own account.', 422);
        }

        activity()->performedOn($user)->log('User deleted.');

        $this->userService->delete($user);

        return ApiResponse::respondSuccess('User deleted successfully.');
    }
}
