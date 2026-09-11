<?php

namespace App\Http\Controllers\V1\TeacherApi;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\TeacherApi\ForgotPasswordRequest;
use App\Http\Requests\V1\TeacherApi\LoginTeacherRequest;
use App\Http\Requests\V1\TeacherApi\RegisterTeacherRequest;
use App\Http\Requests\V1\TeacherApi\ResetPasswordRequest;
use App\Http\Requests\V1\TeacherApi\UpdateTeacherProfileRequest;
use App\Http\Resources\TeacherApi\TeacherProfileResource;
use App\Services\TeacherApi\TeacherAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly TeacherAuthService $teacherAuthService,
    ) {}

    public function register(RegisterTeacherRequest $request): JsonResponse
    {
        $teacher = $this->teacherAuthService->register($request->validated());

        return ApiResponse::respondWithResource(
            new TeacherProfileResource($teacher->load('college')),
            'Registration received. An administrator will review your account before you can sign in.',
            201,
        );
    }

    public function login(LoginTeacherRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $teacher = $this->teacherAuthService->attemptLogin(
            $validated,
            (bool) ($validated['remember'] ?? false),
        );

        $request->session()->regenerate();

        return ApiResponse::respondWithResource(
            new TeacherProfileResource($teacher->load('college')),
            'Logged in successfully.',
        );
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new TeacherProfileResource($request->user('teacher')->load('college')),
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $this->teacherAuthService->logout();

        $request->session()->regenerate();

        return ApiResponse::respondSuccess('Logged out successfully.');
    }

    public function updateProfile(UpdateTeacherProfileRequest $request): JsonResponse
    {
        $teacher = $this->teacherAuthService->updateProfile(
            $request->user('teacher'),
            $request->validated(),
        );

        return ApiResponse::respondWithResource(
            new TeacherProfileResource($teacher),
            'Profile updated successfully.',
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->teacherAuthService->sendResetLink($request->validated('email'));

        return ApiResponse::respondSuccess('If the email exists, a reset link has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->teacherAuthService->resetPassword($request->validated());

        return ApiResponse::respondSuccess('Password has been reset successfully.');
    }
}
