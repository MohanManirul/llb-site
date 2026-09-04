<?php

namespace App\Http\Controllers\V1\Admin\ActivityLog;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ActivityLog\IndexActivityLogRequest;
use App\Http\Resources\ActivityLog\ActivityLogResource;
use App\Models\ActivityLog;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view activity logs', only: ['index', 'filterOptions']),
            new Middleware('permission:delete activity logs', only: ['destroy']),
        ];
    }

    public function index(IndexActivityLogRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            ActivityLogResource::collection($this->activityLogService->paginate($request->filters())),
            'Activity logs retrieved successfully.',
        );
    }

    public function filterOptions(): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->activityLogService->filterOptions(),
            'Activity log filters retrieved successfully.',
        );
    }

    public function destroy(ActivityLog $activityLog): JsonResponse
    {
        if ($activityLog->subject_type === ActivityLog::class) {
            return ApiResponse::respondForbidden(
                'A record of a deleted activity log cannot itself be deleted.',
            );
        }

        DB::transaction(function () use ($activityLog) {

            activity()->performedOn($activityLog)->log('Deleted an activity log.');

            $this->activityLogService->delete($activityLog);
        });

        return ApiResponse::respondSuccess('Activity log deleted successfully.');
    }
}
