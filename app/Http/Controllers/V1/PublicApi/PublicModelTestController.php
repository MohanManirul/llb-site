<?php

namespace App\Http\Controllers\V1\PublicApi;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\PublicApi\IndexPublicModelTestRequest;
use App\Http\Resources\PublicApi\PublicModelTestResource;
use App\Models\ModelTest;
use App\Services\PublicApi\PublicModelTestService;
use Illuminate\Http\JsonResponse;

class PublicModelTestController extends Controller
{
    public function __construct(
        private readonly PublicModelTestService $publicModelTestService,
    ) {}

    public function index(IndexPublicModelTestRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            PublicModelTestResource::collection($this->publicModelTestService->paginate($request->filters())),
            'Model tests retrieved successfully.',
        );
    }

    public function show(ModelTest $modelTest): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new PublicModelTestResource($this->publicModelTestService->show($modelTest)),
            'Model test retrieved successfully.',
        );
    }
}
