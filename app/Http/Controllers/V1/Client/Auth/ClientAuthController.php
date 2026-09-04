<?php

namespace App\Http\Controllers\V1\Client\Auth;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\ClientAuthResource;
use App\Http\Resources\Client\ClientResource;
use App\Services\Auth\ClientAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientAuthController extends Controller
{
    public function __construct(
        private readonly ClientAuthService $clientAuthService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = DB::transaction(function () use ($request) {
            $result = $this->clientAuthService->login($request->validated());

            activity()->causedBy($result['client'])->performedOn($result['client'])->log('Client signed in.');

            return $result;
        });

        return ApiResponse::respondWithResource(
            new ClientAuthResource($result),
            'Logged in successfully.',
        );
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::respondWithResource(new ClientResource($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        DB::transaction(function () use ($request) {
            activity()->performedOn($request->user())->log('Client signed out.');

            $request->user()->currentAccessToken()->delete();
        });

        return ApiResponse::respondSuccess('Logged out successfully.');
    }
}
