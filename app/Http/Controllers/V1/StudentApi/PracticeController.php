<?php

namespace App\Http\Controllers\V1\StudentApi;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StudentApi\IndexPracticeSessionRequest;
use App\Http\Requests\V1\StudentApi\PracticeQuestionRequest;
use App\Http\Requests\V1\StudentApi\StorePracticeSessionRequest;
use App\Http\Resources\StudentApi\PracticeQuestionResource;
use App\Http\Resources\StudentApi\PracticeSessionResource;
use App\Http\Resources\StudentApi\PracticeSubjectResource;
use App\Services\StudentApi\PracticeService;
use Illuminate\Http\JsonResponse;

class PracticeController extends Controller
{
    public function __construct(
        private readonly PracticeService $practiceService,
    ) {}

    public function subjects(): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            PracticeSubjectResource::collection($this->practiceService->subjects()),
            'Practice subjects retrieved successfully.',
        );
    }

    public function questions(PracticeQuestionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return ApiResponse::respondWithResourceCollection(
            PracticeQuestionResource::collection(
                $this->practiceService->questions($validated, (int) ($validated['count'] ?? 10)),
            ),
            'Practice questions retrieved successfully.',
        );
    }

    public function store(StorePracticeSessionRequest $request): JsonResponse
    {
        $session = $this->practiceService->record($request->user('student'), $request->validated());

        return ApiResponse::respondWithResource(
            new PracticeSessionResource($session),
            'Practice session recorded successfully.',
            201,
        );
    }

    public function history(IndexPracticeSessionRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            PracticeSessionResource::collection(
                $this->practiceService->history($request->user('student'), $request->filters()),
            ),
            'Practice history retrieved successfully.',
        );
    }
}
