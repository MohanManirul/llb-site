<?php

namespace App\Http\Controllers\V1\Admin\Report;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Report\IndexDownloadReportRequest;
use App\Models\StudyMaterial;
use App\Services\Analytics\AnalyticsReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AnalyticsReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view dashboard', only: ['live', 'downloads', 'materialFiles']),
        ];
    }

    public function __construct(
        private readonly AnalyticsReportService $analyticsReportService,
    ) {}

    public function live(): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->analyticsReportService->live(),
            'Live visitors retrieved successfully.',
        );
    }

    public function downloads(IndexDownloadReportRequest $request): JsonResponse
    {
        $page = $this->analyticsReportService->downloads($request->filters());

        return ApiResponse::respondWithSuccess([
            'data' => $page->getCollection()->map(fn (StudyMaterial $material) => [
                'id' => $material->id,
                'title_bn' => $material->title_bn,
                'title_en' => $material->title_en,
                'type' => $material->type,
                'subject' => $material->subject?->name_bn,
                'download_count' => $material->download_count,
                'period_downloads' => (int) $material->getAttribute('period_downloads'),
                'unique_visitors' => (int) $material->getAttribute('unique_visitors'),
                'view_count' => $material->view_count,
                'last_downloaded_at' => $material->getAttribute('last_downloaded_at'),
            ]),
            'links' => [
                'prev' => $page->previousPageUrl(),
                'next' => $page->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
            ],
        ], 'Download report retrieved successfully.');
    }

    public function materialFiles(StudyMaterial $studyMaterial): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->analyticsReportService->filesFor($studyMaterial),
            'File report retrieved successfully.',
        );
    }
}
