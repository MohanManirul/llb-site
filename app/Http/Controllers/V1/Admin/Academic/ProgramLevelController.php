<?php

namespace App\Http\Controllers\V1\Admin\Academic;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Academic\StoreProgramLevelRequest;
use App\Http\Requests\V1\Admin\Academic\UpdateProgramLevelRequest;
use App\Http\Resources\Academic\ProgramLevelResource;
use App\Models\ProgramLevel;
use App\Services\Academic\ProgramLevelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProgramLevelController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:create academic structure', only: ['store']),
            new Middleware('permission:edit academic structure', only: ['update']),
            new Middleware('permission:delete academic structure', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly ProgramLevelService $programLevelService,
    ) {}

    public function store(StoreProgramLevelRequest $request): JsonResponse
    {
        $level = $this->programLevelService->create($request->validated());

        activity()->performedOn($level)->log('Program level created.');

        return ApiResponse::respondWithResource(
            new ProgramLevelResource($level),
            'Level created successfully.',
            201,
        );
    }

    public function update(UpdateProgramLevelRequest $request, ProgramLevel $programLevel): JsonResponse
    {
        $level = $this->programLevelService->update($programLevel, $request->validated());

        activity()->performedOn($level)->log('Program level updated.');

        return ApiResponse::respondWithResource(
            new ProgramLevelResource($level),
            'Level updated successfully.',
        );
    }

    public function destroy(ProgramLevel $programLevel): JsonResponse
    {
        activity()->performedOn($programLevel)->log('Program level deleted.');

        $this->programLevelService->delete($programLevel);

        return ApiResponse::respondSuccess('Level deleted successfully.');
    }
}
