<?php

namespace App\Http\Controllers\V1\PublicApi;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicApi\PublicCollegeResource;
use App\Services\PublicApi\PublicCollegeService;
use App\Support\Locale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollegeController extends Controller
{
    public function __construct(
        private readonly PublicCollegeService $publicCollegeService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            PublicCollegeResource::collection($this->publicCollegeService->list(
                $request->string('search')->toString() ?: null,
                Locale::resolve($request->string('locale')->toString()),
            )),
            'Colleges retrieved successfully.',
        );
    }

    public function options(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            PublicCollegeResource::collection($this->publicCollegeService->list(
                $request->string('search')->toString() ?: null,
                Locale::resolve($request->string('locale')->toString()),
            )),
            'Colleges retrieved successfully.',
        );
    }
}
