<?php

namespace App\Http\Controllers\V1\Admin\SiteSetting;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\SiteSetting\UpdateSiteSettingRequest;
use App\Http\Resources\SiteSetting\SiteSettingResource;
use App\Models\SiteSetting;
use App\Services\SiteSetting\SiteSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SiteSettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view site settings', only: ['show']),
            new Middleware('permission:edit site settings', only: ['update']),
        ];
    }

    public function __construct(
        private readonly SiteSettingService $siteSettingService,
    ) {}

    public function show(): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new SiteSettingResource($this->siteSettingService->show()),
            'Site settings retrieved successfully.',
        );
    }

    public function update(UpdateSiteSettingRequest $request): JsonResponse
    {
        $current = SiteSetting::current();
        $validated = $request->safe()->except(['logo', 'favicon', 'remove_logo', 'remove_favicon']);

        $validated['logo'] = $this->storeFile(
            $request, 'logo', $current->logo, $request->boolean('remove_logo'),
        );
        $validated['favicon'] = $this->storeFile(
            $request, 'favicon', $current->favicon, $request->boolean('remove_favicon'),
        );

        return ApiResponse::respondWithResource(
            new SiteSettingResource($this->siteSettingService->update($validated)),
            'Site settings updated successfully.',
        );
    }
}
