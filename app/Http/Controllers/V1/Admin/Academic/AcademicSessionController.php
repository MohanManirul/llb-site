<?php

namespace App\Http\Controllers\V1\Admin\Academic;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Academic\IndexAcademicSessionRequest;
use App\Http\Requests\V1\Admin\Academic\StoreAcademicSessionRequest;
use App\Http\Requests\V1\Admin\Academic\UpdateAcademicSessionRequest;
use App\Http\Resources\Academic\AcademicSessionResource;
use App\Models\AcademicSession;
use App\Services\Academic\AcademicSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AcademicSessionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view academic structure', only: ['index', 'show', 'options']),
            new Middleware('permission:create academic structure', only: ['store']),
            new Middleware('permission:edit academic structure', only: ['update', 'markCurrent']),
            new Middleware('permission:delete academic structure', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly AcademicSessionService $academicSessionService,
    ) {}

    public function index(IndexAcademicSessionRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            AcademicSessionResource::collection($this->academicSessionService->paginate($request->filters())),
            'Sessions retrieved successfully.',
        );
    }

    public function options(): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->academicSessionService->options(),
            'Sessions retrieved successfully.',
        );
    }

    public function store(StoreAcademicSessionRequest $request): JsonResponse
    {
        $session = $this->academicSessionService->create($request->validated());

        activity()->performedOn($session)->log('Academic session created.');

        return ApiResponse::respondWithResource(
            new AcademicSessionResource($session),
            'Session created successfully.',
            201,
        );
    }

    public function show(AcademicSession $academicSession): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new AcademicSessionResource($academicSession),
            'Session retrieved successfully.',
        );
    }

    public function update(UpdateAcademicSessionRequest $request, AcademicSession $academicSession): JsonResponse
    {
        $session = $this->academicSessionService->update($academicSession, $request->validated());

        activity()->performedOn($session)->log('Academic session updated.');

        return ApiResponse::respondWithResource(
            new AcademicSessionResource($session),
            'Session updated successfully.',
        );
    }

    public function markCurrent(AcademicSession $academicSession): JsonResponse
    {
        $session = $this->academicSessionService->markCurrent($academicSession);

        activity()->performedOn($session)->log('Academic session marked current.');

        return ApiResponse::respondWithResource(
            new AcademicSessionResource($session),
            'Session marked as current.',
        );
    }

    public function destroy(AcademicSession $academicSession): JsonResponse
    {
        activity()->performedOn($academicSession)->log('Academic session deleted.');

        $this->academicSessionService->delete($academicSession);

        return ApiResponse::respondSuccess('Session deleted successfully.');
    }
}
