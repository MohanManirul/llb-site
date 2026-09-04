<?php

namespace App\Http\Controllers\V1\Admin\Question;

use App\Enums\ContentStatus;
use App\Enums\ExamStage;
use App\Enums\QuestionType;
use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Question\IndexQuestionRequest;
use App\Http\Requests\V1\Admin\Question\StoreQuestionRequest;
use App\Http\Requests\V1\Admin\Question\UpdateQuestionRequest;
use App\Http\Resources\Question\QuestionDetailResource;
use App\Http\Resources\Question\QuestionResource;
use App\Models\Question;
use App\Services\Question\QuestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class QuestionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view questions', only: ['index', 'show', 'filterOptions']),
            new Middleware('permission:create questions', only: ['store']),
            new Middleware('permission:edit questions', only: ['update']),
            new Middleware('permission:publish questions', only: ['publish', 'unpublish']),
            new Middleware('permission:delete questions', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly QuestionService $questionService,
    ) {}

    public function index(IndexQuestionRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            QuestionResource::collection($this->questionService->paginate($request->filters())),
            'Questions retrieved successfully.',
            additional: ['status_count' => $this->questionService->statusCounts()],
        );
    }

    public function filterOptions(): JsonResponse
    {
        return ApiResponse::respondWithSuccess([
            'types' => QuestionType::labels(),
            'statuses' => ContentStatus::labels(),
            'exam_stages' => ExamStage::labels(),
        ], 'Filter options retrieved successfully.');
    }

    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $options = $validated['options'] ?? [];
        unset($validated['options']);

        $question = $this->questionService->create($validated, $options, $request->user()->id);

        activity()->performedOn($question)->log('Question created.');

        return ApiResponse::respondWithResource(
            new QuestionDetailResource($question),
            'Question created successfully.',
            201,
        );
    }

    public function show(Question $question): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new QuestionDetailResource($question->load(['subject.program', 'options'])),
            'Question retrieved successfully.',
        );
    }

    public function update(UpdateQuestionRequest $request, Question $question): JsonResponse
    {
        $validated = $request->validated();
        $options = $validated['options'] ?? null;
        unset($validated['options']);

        $question = $this->questionService->update($question, $validated, $options, $request->user()->id);

        activity()->performedOn($question)->log('Question updated.');

        return ApiResponse::respondWithResource(
            new QuestionDetailResource($question->load('subject.program')),
            'Question updated successfully.',
        );
    }

    public function publish(Request $request, Question $question): JsonResponse
    {
        $question = $this->questionService->publish($question, $request->user()->id);

        activity()->performedOn($question)->log('Question published.');

        return ApiResponse::respondWithResource(
            new QuestionResource($question),
            'Question published successfully.',
        );
    }

    public function unpublish(Request $request, Question $question): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in([ContentStatus::Draft->value, ContentStatus::Archived->value])],
        ]);

        $status = ContentStatus::from($validated['status'] ?? ContentStatus::Draft->value);

        $question = $this->questionService->unpublish($question, $status, $request->user()->id);

        activity()->performedOn($question)->log('Question unpublished.');

        return ApiResponse::respondWithResource(
            new QuestionResource($question),
            'Question unpublished successfully.',
        );
    }

    public function destroy(Question $question): JsonResponse
    {
        activity()->performedOn($question)->log('Question deleted.');

        $this->questionService->delete($question);

        return ApiResponse::respondSuccess('Question deleted successfully.');
    }
}
