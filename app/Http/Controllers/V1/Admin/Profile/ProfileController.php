<?php

namespace App\Http\Controllers\V1\Admin\Profile;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Profile\UpdateProfileRequest;
use App\Models\Client;
use App\Models\User;
use App\Services\Profile\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::respondWithSuccess([
            'user' => $this->presentUser($user),
        ], 'Profile retrieved successfully.');
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $account = $request->user();

        $user = $this->profileService->update(
            $account,
            $request->validated(),
            $this->storeFile($request, 'image', $account->image, $request->boolean('remove_image')),
        );

        activity()->performedOn($user)->log('Profile updated.');

        return ApiResponse::respondWithSuccess(
            $this->presentUser($user),
            'Profile updated successfully.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function presentUser(User|Client $user): array
    {
        return [
            ...$user->only(['id', 'name', 'email']),
            'image_url' => $user->image_url,
            'thumbnail_url' => $user->thumbnail_url,
            'photo_editable' => true,
        ];
    }
}
