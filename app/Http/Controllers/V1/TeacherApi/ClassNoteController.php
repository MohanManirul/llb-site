<?php

namespace App\Http\Controllers\V1\TeacherApi;

use App\Enums\ContentStatus;
use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\TeacherApi\IndexClassNoteRequest;
use App\Http\Requests\V1\TeacherApi\StoreClassNoteRequest;
use App\Http\Requests\V1\TeacherApi\UpdateClassNoteRequest;
use App\Http\Resources\TeacherApi\ClassNoteResource;
use App\Models\ClassNote;
use App\Services\TeacherApi\TeacherClassNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ClassNoteController extends Controller
{
    public function __construct(
        private readonly TeacherClassNoteService $classNoteService,
    ) {}

    public function index(IndexClassNoteRequest $request): JsonResponse
    {
        $teacher = $request->user('teacher');

        return ApiResponse::respondWithResourceCollection(
            ClassNoteResource::collection($this->classNoteService->paginate($teacher, $request->filters())),
            'Class notes retrieved successfully.',
            additional: ['status_count' => $this->classNoteService->statusCounts($teacher)],
        );
    }

    public function store(StoreClassNoteRequest $request): JsonResponse
    {
        $note = $this->classNoteService->create(
            $request->user('teacher'),
            $request->safe()->except(['attachment', 'remove_attachment']),
            $request->file('attachment'),
        );

        return ApiResponse::respondWithResource(
            new ClassNoteResource($note),
            'Class note created successfully.',
            201,
        );
    }

    public function show(Request $request, ClassNote $classNote): JsonResponse
    {
        $this->authorizeOwnership($request, $classNote);

        return ApiResponse::respondWithResource(
            new ClassNoteResource($classNote->load(['session', 'subject'])),
            'Class note retrieved successfully.',
        );
    }

    public function update(UpdateClassNoteRequest $request, ClassNote $classNote): JsonResponse
    {
        $this->authorizeOwnership($request, $classNote);

        $note = $this->classNoteService->update(
            $classNote,
            $request->safe()->except(['attachment', 'remove_attachment']),
            $request->file('attachment'),
            $request->boolean('remove_attachment'),
        );

        return ApiResponse::respondWithResource(
            new ClassNoteResource($note),
            'Class note updated successfully.',
        );
    }

    public function publish(Request $request, ClassNote $classNote): JsonResponse
    {
        $this->authorizeOwnership($request, $classNote);

        return ApiResponse::respondWithResource(
            new ClassNoteResource($this->classNoteService->publish($classNote)),
            'Class note published successfully.',
        );
    }

    public function unpublish(Request $request, ClassNote $classNote): JsonResponse
    {
        $this->authorizeOwnership($request, $classNote);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in([ContentStatus::Draft->value, ContentStatus::Archived->value])],
        ]);

        $status = ContentStatus::from($validated['status'] ?? ContentStatus::Draft->value);

        return ApiResponse::respondWithResource(
            new ClassNoteResource($this->classNoteService->unpublish($classNote, $status)),
            'Class note unpublished successfully.',
        );
    }

    public function attachment(Request $request, ClassNote $classNote): Response
    {
        $this->authorizeOwnership($request, $classNote);

        abort_unless($classNote->attachment_path !== null, 404);
        abort_unless(Storage::disk($classNote->attachment_disk)->exists($classNote->attachment_path), 404);

        return Storage::disk($classNote->attachment_disk)->response(
            $classNote->attachment_path,
            $classNote->attachment_name,
            ['Cache-Control' => 'private, no-store'],
        );
    }

    public function destroy(Request $request, ClassNote $classNote): JsonResponse
    {
        $this->authorizeOwnership($request, $classNote);

        $this->classNoteService->delete($classNote);

        return ApiResponse::respondSuccess('Class note deleted successfully.');
    }

    private function authorizeOwnership(Request $request, ClassNote $classNote): void
    {
        abort_unless($classNote->college_id === $request->user('teacher')->college_id, 404);
    }
}
