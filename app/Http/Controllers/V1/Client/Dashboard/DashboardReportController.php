<?php

namespace App\Http\Controllers\V1\Client\Dashboard;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Dashboard\IndexDashboardReportRequest;
use App\Models\Client;
use App\Services\Dashboard\DashboardReportService;
use Illuminate\Http\JsonResponse;

class DashboardReportController extends Controller
{
    public function __construct(
        private readonly DashboardReportService $dashboardReportService,
    ) {}

    public function index(IndexDashboardReportRequest $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();

        return ApiResponse::respondWithSuccess(
            $this->dashboardReportService->clientReport(
                $client,
                $request->dateFrom(),
                $request->dateTo(),
            ),
            'Dashboard report retrieved successfully.',
        );
    }
}
