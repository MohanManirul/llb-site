<?php

namespace App\Http\Controllers\V1\StudentApi;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StudentApi\IndexAttemptRequest;
use App\Http\Requests\V1\StudentApi\SaveAnswerRequest;
use App\Http\Resources\StudentApi\AttemptResource;
use App\Http\Resources\StudentApi\AttemptResultResource;
use App\Models\ModelTest;
use App\Models\TestAttempt;
use App\Services\StudentApi\TestAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttemptController extends Controller
{
    public function __construct(
        private readonly TestAttemptService $testAttemptService,
    ) {}

    public function start(Request $request, ModelTest $modelTest): JsonResponse
    {
        $attempt = $this->testAttemptService->start($request->user('student'), $modelTest);

        return ApiResponse::respondWithResource(
            new AttemptResource($attempt->load(['modelTest', 'answers'])),
            'Attempt started successfully.',
            201,
        );
    }

    public function index(IndexAttemptRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            AttemptResource::collection(
                $this->testAttemptService->paginate($request->user('student'), $request->filters()),
            ),
            'Attempts retrieved successfully.',
        );
    }

    public function show(Request $request, TestAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);

        return ApiResponse::respondWithResource(
            new AttemptResource($this->testAttemptService->show($attempt)),
            'Attempt retrieved successfully.',
        );
    }

    public function saveAnswer(SaveAnswerRequest $request, TestAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);

        $validated = $request->validated();

        $this->testAttemptService->saveAnswer(
            $attempt,
            (int) $validated['question_id'],
            isset($validated['question_option_id']) ? (int) $validated['question_option_id'] : null,
        );

        return ApiResponse::respondSuccess('Answer saved successfully.');
    }

    public function submit(Request $request, TestAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);

        $submitted = $this->testAttemptService->submit($attempt);

        return ApiResponse::respondWithResource(
            new AttemptResource($submitted->load('modelTest')),
            'Attempt submitted successfully.',
        );
    }

    public function result(Request $request, TestAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);

        return ApiResponse::respondWithResource(
            new AttemptResultResource($this->testAttemptService->result($attempt)),
            'Result retrieved successfully.',
        );
    }

    private function authorizeAttempt(Request $request, TestAttempt $attempt): void
    {
        abort_unless($attempt->student_id === $request->user('student')->id, 404);
    }
}
