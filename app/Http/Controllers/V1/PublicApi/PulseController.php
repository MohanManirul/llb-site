<?php

namespace App\Http\Controllers\V1\PublicApi;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\Analytics\VisitorTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PulseController extends Controller
{
    public function __construct(
        private readonly VisitorTrackingService $visitorTrackingService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['nullable', 'string', 'max:500'],
        ]);

        $this->visitorTrackingService->pulse($request, $validated['path'] ?? null);

        return ApiResponse::respondSuccess('ok')
            ->header('Cache-Control', 'no-store');
    }
}
