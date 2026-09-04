<?php

namespace App\Http\Controllers\V1\PublicApi;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\PublicApi\IndexPublicNoticeRequest;
use App\Http\Resources\PublicApi\PublicNoticeDetailResource;
use App\Http\Resources\PublicApi\PublicNoticeResource;
use App\Models\Notice;
use App\Services\PublicApi\PublicNoticeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class NoticeController extends Controller
{
    public function __construct(
        private readonly PublicNoticeService $publicNoticeService,
    ) {}

    public function index(IndexPublicNoticeRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            PublicNoticeResource::collection(
                $this->publicNoticeService->paginate($request->filters()),
            ),
            'Notices retrieved successfully.',
        );
    }

    public function show(Notice $notice): JsonResponse
    {
        abort_unless($notice->isPubliclyVisible(), 404);

        return ApiResponse::respondWithResource(
            new PublicNoticeDetailResource($this->publicNoticeService->show($notice)),
            'Notice retrieved successfully.',
        );
    }

    public function attachment(Notice $notice): Response
    {
        abort_unless($notice->isPubliclyVisible(), 404);
        abort_unless($notice->attachment_path !== null, 404);
        abort_unless(Storage::disk($notice->attachment_disk)->exists($notice->attachment_path), 404);

        Notice::whereKey($notice->id)->increment('attachment_download_count');

        return Storage::disk($notice->attachment_disk)->download(
            $notice->attachment_path,
            $notice->slug.'.pdf',
            ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store'],
        );
    }
}
