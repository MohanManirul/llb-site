<?php

namespace App\Http\Controllers\V1\Admin\Academic;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Academic\IndexProgramRequest;
use App\Http\Requests\V1\Admin\Academic\StoreProgramRequest;
use App\Http\Requests\V1\Admin\Academic\UpdateProgramRequest;
use App\Http\Resources\Academic\ProgramResource;
use App\Models\Program;
use App\Services\Academic\ProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProgramController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view academic structure', only: ['index', 'show', 'options']),
            new Middleware('permission:create academic structure', only: ['store']),
            new Middleware('permission:edit academic structure', only: ['update']),
            new Middleware('permission:delete academic structure', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly ProgramService $programService,
    ) {}

    public function index(IndexProgramRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            ProgramResource::collection($this->programService->paginate($request->filters())),
            'Programs retrieved successfully.',
        );
    }

    public function options(): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->programService->options(),
            'Programs retrieved successfully.',
        );
    }

    public function store(StoreProgramRequest $request): JsonResponse
    {
        $program = $this->programService->create($request->validated());

        activity()->performedOn($program)->log('Program created.');

        return ApiResponse::respondWithResource(
            new ProgramResource($program),
            'Program created successfully.',
            201,
        );
    }

    public function show(Program $program): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new ProgramResource($program->load('levels')->loadCount('subjects')),
            'Program retrieved successfully.',
        );
    }

    public function update(UpdateProgramRequest $request, Program $program): JsonResponse
    {
        $program = $this->programService->update($program, $request->validated());

        activity()->performedOn($program)->log('Program updated.');

        return ApiResponse::respondWithResource(
            new ProgramResource($program),
            'Program updated successfully.',
        );
    }

    public function destroy(Program $program): JsonResponse
    {
        activity()->performedOn($program)->log('Program deleted.');

        $this->programService->delete($program);

        return ApiResponse::respondSuccess('Program deleted successfully.');
    }
}
