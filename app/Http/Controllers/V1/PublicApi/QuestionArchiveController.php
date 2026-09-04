<?php

namespace App\Http\Controllers\V1\PublicApi;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\PublicApi\IndexArchiveQuestionRequest;
use App\Http\Resources\PublicApi\ArchiveQuestionResource;
use App\Services\PublicApi\QuestionArchiveService;
use Illuminate\Http\JsonResponse;

class QuestionArchiveController extends Controller
{
    public function __construct(
        private readonly QuestionArchiveService $questionArchiveService,
    ) {}

    public function mcq(IndexArchiveQuestionRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            ArchiveQuestionResource::collection($this->questionArchiveService->mcq($request->filters())),
            'Questions retrieved successfully.',
        );
    }

    public function written(IndexArchiveQuestionRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            ArchiveQuestionResource::collection($this->questionArchiveService->written($request->filters())),
            'Questions retrieved successfully.',
        );
    }

    public function filters(): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->questionArchiveService->filters(),
            'Filter options retrieved successfully.',
        );
    }
}
