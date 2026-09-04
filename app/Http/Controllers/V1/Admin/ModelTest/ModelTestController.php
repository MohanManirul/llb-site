<?php

namespace App\Http\Controllers\V1\Admin\ModelTest;

use App\Enums\ContentStatus;
use App\Enums\ExamStage;
use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ModelTest\IndexModelTestRequest;
use App\Http\Requests\V1\Admin\ModelTest\StoreModelTestRequest;
use App\Http\Requests\V1\Admin\ModelTest\UpdateModelTestRequest;
use App\Http\Resources\ModelTest\ModelTestDetailResource;
use App\Http\Resources\ModelTest\ModelTestResource;
use App\Models\ModelTest;
use App\Services\ModelTest\ModelTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class ModelTestController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view model tests', only: ['index', 'show', 'filterOptions']),
            new Middleware('permission:create model tests', only: ['store']),
            new Middleware('permission:edit model tests', only: ['update']),
            new Middleware('permission:publish model tests', only: ['publish', 'unpublish']),
            new Middleware('permission:delete model tests', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly ModelTestService $modelTestService,
    ) {}

    public function index(IndexModelTestRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            ModelTestResource::collection($this->modelTestService->paginate($request->filters())),
            'Model tests retrieved successfully.',
            additional: ['status_count' => $this->modelTestService->statusCounts()],
        );
    }

    public function filterOptions(): JsonResponse
    {
        return ApiResponse::respondWithSuccess([
            'statuses' => ContentStatus::labels(),
            'exam_stages' => ExamStage::labels(),
        ], 'Filter options retrieved successfully.');
    }

    public function store(StoreModelTestRequest $request): JsonResponse
    {
        $modelTest = $this->modelTestService->create($request->validated(), $request->user()->id);

        activity()->performedOn($modelTest)->log('Model test created.');

        return ApiResponse::respondWithResource(
            new ModelTestDetailResource($modelTest->load('program')),
            'Model test created successfully.',
            201,
        );
    }

    public function show(ModelTest $modelTest): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new ModelTestDetailResource(
                $modelTest->load(['program', 'questions.options'])->loadCount('attempts'),
            ),
            'Model test retrieved successfully.',
        );
    }

    public function update(UpdateModelTestRequest $request, ModelTest $modelTest): JsonResponse
    {
        $modelTest = $this->modelTestService->update($modelTest, $request->validated(), $request->user()->id);

        activity()->performedOn($modelTest)->log('Model test updated.');

        return ApiResponse::respondWithResource(
            new ModelTestDetailResource($modelTest->load('program')),
            'Model test updated successfully.',
        );
    }

    public function publish(Request $request, ModelTest $modelTest): JsonResponse
    {
        $modelTest = $this->modelTestService->publish($modelTest, $request->user()->id);

        activity()->performedOn($modelTest)->log('Model test published.');

        return ApiResponse::respondWithResource(
            new ModelTestResource($modelTest),
            'Model test published successfully.',
        );
    }

    public function unpublish(Request $request, ModelTest $modelTest): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in([ContentStatus::Draft->value, ContentStatus::Archived->value])],
        ]);

        $status = ContentStatus::from($validated['status'] ?? ContentStatus::Draft->value);

        $modelTest = $this->modelTestService->unpublish($modelTest, $status, $request->user()->id);

        activity()->performedOn($modelTest)->log('Model test unpublished.');

        return ApiResponse::respondWithResource(
            new ModelTestResource($modelTest),
            'Model test unpublished successfully.',
        );
    }

    public function destroy(ModelTest $modelTest): JsonResponse
    {
        activity()->performedOn($modelTest)->log('Model test deleted.');

        $this->modelTestService->delete($modelTest);

        return ApiResponse::respondSuccess('Model test deleted successfully.');
    }
}
