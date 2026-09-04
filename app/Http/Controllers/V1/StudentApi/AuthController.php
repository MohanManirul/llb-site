<?php

namespace App\Http\Controllers\V1\StudentApi;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StudentApi\ForgotPasswordRequest;
use App\Http\Requests\V1\StudentApi\LoginStudentRequest;
use App\Http\Requests\V1\StudentApi\RegisterStudentRequest;
use App\Http\Requests\V1\StudentApi\ResetPasswordRequest;
use App\Http\Requests\V1\StudentApi\UpdateStudentProfileRequest;
use App\Http\Resources\StudentApi\StudentProfileResource;
use App\Services\StudentApi\StudentAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly StudentAuthService $studentAuthService,
    ) {}

    public function register(RegisterStudentRequest $request): JsonResponse
    {
        $student = $this->studentAuthService->register($request->validated());

        $request->session()->regenerate();

        return ApiResponse::respondWithResource(
            new StudentProfileResource($student),
            'Registered successfully.',
            201,
        );
    }

    public function login(LoginStudentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $student = $this->studentAuthService->attemptLogin(
            $validated,
            (bool) ($validated['remember'] ?? false),
        );

        $request->session()->regenerate();

        return ApiResponse::respondWithResource(
            new StudentProfileResource($student),
            'Logged in successfully.',
        );
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new StudentProfileResource($request->user('student')->load('program')),
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $this->studentAuthService->logout();

        $request->session()->regenerate();

        return ApiResponse::respondSuccess('Logged out successfully.');
    }

    public function updateProfile(UpdateStudentProfileRequest $request): JsonResponse
    {
        $student = $this->studentAuthService->updateProfile(
            $request->user('student'),
            $request->validated(),
        );

        return ApiResponse::respondWithResource(
            new StudentProfileResource($student),
            'Profile updated successfully.',
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->studentAuthService->sendResetLink($request->validated('email'));

        return ApiResponse::respondSuccess('If the email exists, a reset link has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->studentAuthService->resetPassword($request->validated());

        return ApiResponse::respondSuccess('Password has been reset successfully.');
    }
}
