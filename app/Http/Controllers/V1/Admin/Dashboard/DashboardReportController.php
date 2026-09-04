<?php

namespace App\Http\Controllers\V1\Admin\Dashboard;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Dashboard\IndexDashboardReportRequest;
use App\Services\Dashboard\DashboardReportService;
use App\Traits\ChecksDashboardAccess;
use Illuminate\Http\JsonResponse;

class DashboardReportController extends Controller
{
    use ChecksDashboardAccess;

    public function __construct(
        private readonly DashboardReportService $dashboardReportService,
    ) {}

    public function index(IndexDashboardReportRequest $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::respondWithSuccess(
            $this->dashboardReportService->report(
                $user,
                $request->dateFrom(),
                $request->dateTo(),
                canViewDashboard: $this->canViewCompanyDashboard($user),
                canSeeClient: $this->canSeeProjectClient($user),
                canViewFinance: $this->canViewDashboardFinance($user),
            ),
            'Dashboard report retrieved successfully.',
        );
    }
}
