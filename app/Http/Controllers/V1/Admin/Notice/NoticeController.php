<?php

namespace App\Http\Controllers\V1\Admin\Notice;

use App\Enums\ContentStatus;
use App\Enums\NoticeCategory;
use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Notice\IndexNoticeRequest;
use App\Http\Requests\V1\Admin\Notice\StoreNoticeRequest;
use App\Http\Requests\V1\Admin\Notice\UpdateNoticeRequest;
use App\Http\Resources\Notice\NoticeResource;
use App\Models\Notice;
use App\Services\Notice\NoticeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class NoticeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view notices', only: ['index', 'show', 'filterOptions', 'attachment']),
            new Middleware('permission:create notices', only: ['store']),
            new Middleware('permission:edit notices', only: ['update']),
            new Middleware('permission:publish notices', only: ['publish', 'unpublish']),
            new Middleware('permission:delete notices', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly NoticeService $noticeService,
    ) {}

    public function index(IndexNoticeRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            NoticeResource::collection($this->noticeService->paginate($request->filters())),
            'Notices retrieved successfully.',
            additional: ['status_count' => $this->noticeService->statusCounts()],
        );
    }

    public function filterOptions(): JsonResponse
    {
        return ApiResponse::respondWithSuccess([
            'categories' => NoticeCategory::labels(),
            'statuses' => ContentStatus::labels(),
        ], 'Filter options retrieved successfully.');
    }

    public function store(StoreNoticeRequest $request): JsonResponse
    {
        $validated = $request->safe()->except(['attachment']);

        $notice = $this->noticeService->create(
            $validated,
            $request->file('attachment'),
            $request->user()->id,
        );

        activity()->performedOn($notice)->log('Notice created.');

        return ApiResponse::respondWithResource(
            new NoticeResource($notice),
            'Notice created successfully.',
            201,
        );
    }

    public function show(Notice $notice): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new NoticeResource($notice->load(['program', 'session', 'subject'])),
            'Notice retrieved successfully.',
        );
    }

    public function update(UpdateNoticeRequest $request, Notice $notice): JsonResponse
    {
        $validated = $request->safe()->except(['attachment', 'remove_attachment']);

        $notice = $this->noticeService->update(
            $notice,
            $validated,
            $request->file('attachment'),
            $request->boolean('remove_attachment'),
            $request->user()->id,
        );

        activity()->performedOn($notice)->log('Notice updated.');

        return ApiResponse::respondWithResource(
            new NoticeResource($notice),
            'Notice updated successfully.',
        );
    }

    public function attachment(Notice $notice): Response
    {
        abort_unless($notice->attachment_path !== null, 404);
        abort_unless(Storage::disk($notice->attachment_disk)->exists($notice->attachment_path), 404);

        return Storage::disk($notice->attachment_disk)->response(
            $notice->attachment_path,
            $notice->attachment_name,
            ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store'],
        );
    }

    public function publish(Request $request, Notice $notice): JsonResponse
    {
        $notice = $this->noticeService->publish($notice, $request->user()->id);

        activity()->performedOn($notice)->log('Notice published.');

        return ApiResponse::respondWithResource(
            new NoticeResource($notice),
            'Notice published successfully.',
        );
    }

    public function unpublish(Request $request, Notice $notice): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in([ContentStatus::Draft->value, ContentStatus::Archived->value])],
        ]);

        $status = ContentStatus::from($validated['status'] ?? ContentStatus::Draft->value);

        $notice = $this->noticeService->unpublish($notice, $status, $request->user()->id);

        activity()->performedOn($notice)->log('Notice unpublished.');

        return ApiResponse::respondWithResource(
            new NoticeResource($notice),
            'Notice unpublished successfully.',
        );
    }

    public function destroy(Notice $notice): JsonResponse
    {
        activity()->performedOn($notice)->log('Notice deleted.');

        $this->noticeService->delete($notice);

        return ApiResponse::respondSuccess('Notice deleted successfully.');
    }
}
