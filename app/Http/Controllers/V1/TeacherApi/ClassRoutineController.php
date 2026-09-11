<?php

namespace App\Http\Controllers\V1\TeacherApi;

use App\Enums\ContentStatus;
use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\TeacherApi\IndexCollegeContentRequest;
use App\Http\Requests\V1\TeacherApi\StoreClassRoutineRequest;
use App\Http\Requests\V1\TeacherApi\UpdateClassRoutineRequest;
use App\Http\Resources\TeacherApi\ClassRoutineResource;
use App\Models\ClassRoutine;
use App\Services\TeacherApi\TeacherClassRoutineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ClassRoutineController extends Controller
{
    public function __construct(
        private readonly TeacherClassRoutineService $classRoutineService,
    ) {}

    public function index(IndexCollegeContentRequest $request): JsonResponse
    {
        $teacher = $request->user('teacher');

        return ApiResponse::respondWithResourceCollection(
            ClassRoutineResource::collection($this->classRoutineService->paginate($teacher, $request->filters())),
            'Class routines retrieved successfully.',
            additional: ['status_count' => $this->classRoutineService->statusCounts($teacher)],
        );
    }

    public function store(StoreClassRoutineRequest $request): JsonResponse
    {
        $routine = $this->classRoutineService->create(
            $request->user('teacher'),
            $request->safe()->except(['attachment', 'remove_attachment']),
            $request->file('attachment'),
        );

        return ApiResponse::respondWithResource(
            new ClassRoutineResource($routine),
            'Class routine created successfully.',
            201,
        );
    }

    public function show(Request $request, ClassRoutine $classRoutine): JsonResponse
    {
        $this->authorizeOwnership($request, $classRoutine);

        return ApiResponse::respondWithResource(
            new ClassRoutineResource($classRoutine->load('session')),
            'Class routine retrieved successfully.',
        );
    }

    public function update(UpdateClassRoutineRequest $request, ClassRoutine $classRoutine): JsonResponse
    {
        $this->authorizeOwnership($request, $classRoutine);

        $routine = $this->classRoutineService->update(
            $classRoutine,
            $request->safe()->except(['attachment', 'remove_attachment']),
            $request->file('attachment'),
            $request->boolean('remove_attachment'),
        );

        return ApiResponse::respondWithResource(
            new ClassRoutineResource($routine),
            'Class routine updated successfully.',
        );
    }

    public function publish(Request $request, ClassRoutine $classRoutine): JsonResponse
    {
        $this->authorizeOwnership($request, $classRoutine);

        return ApiResponse::respondWithResource(
            new ClassRoutineResource($this->classRoutineService->publish($classRoutine)),
            'Class routine published successfully.',
        );
    }

    public function unpublish(Request $request, ClassRoutine $classRoutine): JsonResponse
    {
        $this->authorizeOwnership($request, $classRoutine);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in([ContentStatus::Draft->value, ContentStatus::Archived->value])],
        ]);

        $status = ContentStatus::from($validated['status'] ?? ContentStatus::Draft->value);

        return ApiResponse::respondWithResource(
            new ClassRoutineResource($this->classRoutineService->unpublish($classRoutine, $status)),
            'Class routine unpublished successfully.',
        );
    }

    public function attachment(Request $request, ClassRoutine $classRoutine): Response
    {
        $this->authorizeOwnership($request, $classRoutine);

        abort_unless($classRoutine->attachment_path !== null, 404);
        abort_unless(Storage::disk($classRoutine->attachment_disk)->exists($classRoutine->attachment_path), 404);

        return Storage::disk($classRoutine->attachment_disk)->response(
            $classRoutine->attachment_path,
            $classRoutine->attachment_name,
            ['Cache-Control' => 'private, no-store'],
        );
    }

    public function destroy(Request $request, ClassRoutine $classRoutine): JsonResponse
    {
        $this->authorizeOwnership($request, $classRoutine);

        $this->classRoutineService->delete($classRoutine);

        return ApiResponse::respondSuccess('Class routine deleted successfully.');
    }

    private function authorizeOwnership(Request $request, ClassRoutine $classRoutine): void
    {
        abort_unless($classRoutine->college_id === $request->user('teacher')->college_id, 404);
    }
}
