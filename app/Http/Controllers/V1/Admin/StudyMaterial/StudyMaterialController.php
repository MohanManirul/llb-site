<?php

namespace App\Http\Controllers\V1\Admin\StudyMaterial;

use App\Enums\ContentLanguage;
use App\Enums\ContentStatus;
use App\Enums\ExamStage;
use App\Enums\MaterialType;
use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\StudyMaterial\IndexStudyMaterialRequest;
use App\Http\Requests\V1\Admin\StudyMaterial\StoreStudyMaterialRequest;
use App\Http\Requests\V1\Admin\StudyMaterial\UpdateStudyMaterialRequest;
use App\Http\Resources\StudyMaterial\StudyMaterialDetailResource;
use App\Http\Resources\StudyMaterial\StudyMaterialResource;
use App\Models\StudyMaterial;
use App\Services\StudyMaterial\StudyMaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class StudyMaterialController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view study materials', only: ['index', 'show', 'filterOptions']),
            new Middleware('permission:create study materials', only: ['store']),
            new Middleware('permission:edit study materials', only: ['update']),
            new Middleware('permission:publish study materials', only: ['publish', 'unpublish']),
            new Middleware('permission:delete study materials', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly StudyMaterialService $studyMaterialService,
    ) {}

    public function index(IndexStudyMaterialRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            StudyMaterialResource::collection($this->studyMaterialService->paginate($request->filters())),
            'Study materials retrieved successfully.',
            additional: ['status_count' => $this->studyMaterialService->statusCounts()],
        );
    }

    public function filterOptions(): JsonResponse
    {
        return ApiResponse::respondWithSuccess([
            'types' => MaterialType::labels(),
            'statuses' => ContentStatus::labels(),
            'exam_stages' => ExamStage::labels(),
            'content_languages' => ContentLanguage::labels(),
        ], 'Filter options retrieved successfully.');
    }

    public function store(StoreStudyMaterialRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $files = $validated['files'];
        unset($validated['files']);

        $validated['cover_image'] = $this->storeFile($request, 'cover_image');

        $material = $this->studyMaterialService->create($validated, $files, $request->user()->id);

        activity()->performedOn($material)->log('Study material created.');

        return ApiResponse::respondWithResource(
            new StudyMaterialDetailResource($material),
            'Study material created successfully.',
            201,
        );
    }

    public function show(StudyMaterial $studyMaterial): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new StudyMaterialDetailResource(
                $studyMaterial->load(['subject.program', 'session', 'files']),
            ),
            'Study material retrieved successfully.',
        );
    }

    public function update(UpdateStudyMaterialRequest $request, StudyMaterial $studyMaterial): JsonResponse
    {
        $validated = $request->validated();
        unset($validated['remove_cover_image']);

        $validated['cover_image'] = $this->storeFile(
            $request,
            'cover_image',
            $studyMaterial->cover_image,
            $request->boolean('remove_cover_image'),
        );

        $material = $this->studyMaterialService->update($studyMaterial, $validated, $request->user()->id);

        activity()->performedOn($material)->log('Study material updated.');

        return ApiResponse::respondWithResource(
            new StudyMaterialDetailResource($material->load(['subject.program', 'session'])),
            'Study material updated successfully.',
        );
    }

    public function publish(Request $request, StudyMaterial $studyMaterial): JsonResponse
    {
        $material = $this->studyMaterialService->publish($studyMaterial, $request->user()->id);

        activity()->performedOn($material)->log('Study material published.');

        return ApiResponse::respondWithResource(
            new StudyMaterialResource($material),
            'Study material published successfully.',
        );
    }

    public function unpublish(Request $request, StudyMaterial $studyMaterial): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in([ContentStatus::Draft->value, ContentStatus::Archived->value])],
        ]);

        $status = ContentStatus::from($validated['status'] ?? ContentStatus::Draft->value);

        $material = $this->studyMaterialService->unpublish($studyMaterial, $status, $request->user()->id);

        activity()->performedOn($material)->log('Study material unpublished.');

        return ApiResponse::respondWithResource(
            new StudyMaterialResource($material),
            'Study material unpublished successfully.',
        );
    }

    public function destroy(StudyMaterial $studyMaterial): JsonResponse
    {
        activity()->performedOn($studyMaterial)->log('Study material deleted.');

        $this->studyMaterialService->delete($studyMaterial);

        return ApiResponse::respondSuccess('Study material deleted successfully.');
    }
}
