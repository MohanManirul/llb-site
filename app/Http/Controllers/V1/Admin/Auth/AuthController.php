<?php

namespace App\Http\Controllers\V1\Admin\Auth;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\V1\Admin\Auth\RegisterRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Http\Resources\User\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return ApiResponse::respondWithResource(
            new AuthResource($result),
            'Registered successfully.',
            201,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = DB::transaction(function () use ($request) {
            $result = $this->authService->login($request->validated());

            activity()->causedBy($result['user'])->performedOn($result['user'])->log('Signed in.');

            return $result;
        });

        return ApiResponse::respondWithResource(
            new AuthResource($result),
            'Logged in successfully.',
        );
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::respondWithResource(new UserResource($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        DB::transaction(function () use ($request) {
            activity()->performedOn($request->user())->log('Signed out.');

            $request->user()->currentAccessToken()->delete();
        });

        return ApiResponse::respondSuccess('Logged out successfully.');
    }
}
