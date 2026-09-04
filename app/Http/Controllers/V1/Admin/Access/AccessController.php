<?php

namespace App\Http\Controllers\V1\Admin\Access;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Access\GrantLoginRequest;
use App\Http\Requests\V1\Admin\Access\StoreStaffRequest;
use App\Http\Resources\Client\ClientResource;
use App\Http\Resources\User\UserResource;
use App\Models\Client;
use App\Services\Access\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AccessController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:manage access', only: [
                'grantClientLogin', 'revokeClientLogin', 'storeStaff', 'permissions',
            ]),
        ];
    }

    public function __construct(
        private readonly AccessService $accessService,
    ) {}

    public function grantClientLogin(GrantLoginRequest $request, Client $client): JsonResponse
    {
        $client = $this->accessService->grantClientLogin($client, $request->validated('password'));

        activity()->performedOn($client)->log('Client login access granted.');

        return ApiResponse::respondWithResource(
            new ClientResource($client),
            "Login access granted to client {$client->name}.",
            201,
        );
    }

    public function revokeClientLogin(Client $client): JsonResponse
    {
        activity()->performedOn($client)->log('Client login access revoked.');

        $this->accessService->revokeClientLogin($client);

        return ApiResponse::respondSuccess("Login access revoked for client {$client->name}.");
    }

    public function storeStaff(StoreStaffRequest $request): JsonResponse
    {
        $user = $this->accessService->createStaff($request->validated());

        activity()->performedOn($user)->log('Staff account created.');

        return ApiResponse::respondWithResource(
            new UserResource($user),
            'Staff account created.',
            201,
        );
    }

    public function permissions(): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            data: $this->accessService->permissionNames(),
            message: 'Permissions retrieved successfully.',
        );
    }
}
