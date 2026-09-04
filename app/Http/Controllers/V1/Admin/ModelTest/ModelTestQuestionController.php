<?php

namespace App\Http\Controllers\V1\Admin\ModelTest;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ModelTest\AttachQuestionsRequest;
use App\Http\Requests\V1\Admin\ModelTest\ReorderQuestionsRequest;
use App\Http\Resources\ModelTest\ModelTestDetailResource;
use App\Models\ModelTest;
use App\Models\Question;
use App\Services\ModelTest\ModelTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ModelTestQuestionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:edit model tests', only: ['store', 'reorder', 'destroy']),
        ];
    }

    public function __construct(
        private readonly ModelTestService $modelTestService,
    ) {}

    public function store(AttachQuestionsRequest $request, ModelTest $modelTest): JsonResponse
    {
        $validated = $request->validated();

        $modelTest = $this->modelTestService->attachQuestions(
            $modelTest,
            $validated['question_ids'],
            (float) ($validated['marks'] ?? 1),
        );

        activity()->performedOn($modelTest)->log('Model test questions attached.');

        return ApiResponse::respondWithResource(
            new ModelTestDetailResource($modelTest->load('program')),
            'Questions attached successfully.',
        );
    }

    public function reorder(ReorderQuestionsRequest $request, ModelTest $modelTest): JsonResponse
    {
        $validated = $request->validated();

        $modelTest = $this->modelTestService->reorder(
            $modelTest,
            $validated['question_ids'],
            $validated['marks'] ?? [],
        );

        activity()->performedOn($modelTest)->log('Model test questions reordered.');

        return ApiResponse::respondWithResource(
            new ModelTestDetailResource($modelTest->load('program')),
            'Questions reordered successfully.',
        );
    }

    public function destroy(ModelTest $modelTest, Question $question): JsonResponse
    {
        $this->modelTestService->detachQuestion($modelTest, $question);

        activity()->performedOn($modelTest)->log('Model test question detached.');

        return ApiResponse::respondSuccess('Question detached successfully.');
    }
}
