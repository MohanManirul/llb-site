<?php

namespace App\Http\Controllers\V1\TeacherApi;

use App\Enums\ContentStatus;
use App\Enums\NoticeCategory;
use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\TeacherApi\IndexTeacherNoticeRequest;
use App\Http\Requests\V1\TeacherApi\StoreTeacherNoticeRequest;
use App\Http\Requests\V1\TeacherApi\UpdateTeacherNoticeRequest;
use App\Http\Resources\Notice\NoticeResource;
use App\Models\Notice;
use App\Services\TeacherApi\TeacherNoticeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class NoticeController extends Controller
{
    public function __construct(
        private readonly TeacherNoticeService $teacherNoticeService,
    ) {}

    public function index(IndexTeacherNoticeRequest $request): JsonResponse
    {
        $teacher = $request->user('teacher');

        return ApiResponse::respondWithResourceCollection(
            NoticeResource::collection($this->teacherNoticeService->paginate($teacher, $request->filters())),
            'Notices retrieved successfully.',
            additional: ['status_count' => $this->teacherNoticeService->statusCounts($teacher)],
        );
    }

    public function filterOptions(): JsonResponse
    {
        return ApiResponse::respondWithSuccess([
            'categories' => NoticeCategory::labels(),
            'statuses' => ContentStatus::labels(),
        ], 'Filter options retrieved successfully.');
    }

    public function store(StoreTeacherNoticeRequest $request): JsonResponse
    {
        $notice = $this->teacherNoticeService->create(
            $request->user('teacher'),
            $request->safe()->except(['attachment', 'remove_attachment']),
            $request->file('attachment'),
        );

        return ApiResponse::respondWithResource(
            new NoticeResource($notice),
            'Notice created successfully.',
            201,
        );
    }

    public function show(Request $request, Notice $notice): JsonResponse
    {
        $this->authorizeOwnership($request, $notice);

        return ApiResponse::respondWithResource(
            new NoticeResource($notice->load(['session', 'subject'])),
            'Notice retrieved successfully.',
        );
    }

    public function update(UpdateTeacherNoticeRequest $request, Notice $notice): JsonResponse
    {
        $this->authorizeOwnership($request, $notice);

        $notice = $this->teacherNoticeService->update(
            $notice,
            $request->safe()->except(['attachment', 'remove_attachment']),
            $request->file('attachment'),
            $request->boolean('remove_attachment'),
        );

        return ApiResponse::respondWithResource(
            new NoticeResource($notice),
            'Notice updated successfully.',
        );
    }

    public function publish(Request $request, Notice $notice): JsonResponse
    {
        $this->authorizeOwnership($request, $notice);

        return ApiResponse::respondWithResource(
            new NoticeResource($this->teacherNoticeService->publish($notice)),
            'Notice published successfully.',
        );
    }

    public function unpublish(Request $request, Notice $notice): JsonResponse
    {
        $this->authorizeOwnership($request, $notice);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in([ContentStatus::Draft->value, ContentStatus::Archived->value])],
        ]);

        $status = ContentStatus::from($validated['status'] ?? ContentStatus::Draft->value);

        return ApiResponse::respondWithResource(
            new NoticeResource($this->teacherNoticeService->unpublish($notice, $status)),
            'Notice unpublished successfully.',
        );
    }

    public function attachment(Request $request, Notice $notice): Response
    {
        $this->authorizeOwnership($request, $notice);

        abort_unless($notice->attachment_path !== null, 404);
        abort_unless(Storage::disk($notice->attachment_disk)->exists($notice->attachment_path), 404);

        return Storage::disk($notice->attachment_disk)->response(
            $notice->attachment_path,
            $notice->attachment_name,
            ['Cache-Control' => 'private, no-store'],
        );
    }

    public function destroy(Request $request, Notice $notice): JsonResponse
    {
        $this->authorizeOwnership($request, $notice);

        $this->teacherNoticeService->delete($notice);

        return ApiResponse::respondSuccess('Notice deleted successfully.');
    }

    private function authorizeOwnership(Request $request, Notice $notice): void
    {
        abort_unless($notice->college_id === $request->user('teacher')->college_id, 404);
    }
}
