<?php

namespace App\Http\Controllers\V1\Admin\Dashboard;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->dashboardService->forUser($request->user()),
            'Dashboard retrieved successfully.',
        );
    }
}
