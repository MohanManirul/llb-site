<?php

namespace App\Http\Controllers\V1\Client\Dashboard;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\Dashboard\ClientDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ClientDashboardService $clientDashboardService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();

        return ApiResponse::respondWithSuccess(
            $this->clientDashboardService->forClient($client),
            'Dashboard retrieved successfully.',
        );
    }
}
