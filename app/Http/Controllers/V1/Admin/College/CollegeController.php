<?php

namespace App\Http\Controllers\V1\Admin\College;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\College\IndexCollegeRequest;
use App\Http\Requests\V1\Admin\College\StoreCollegeRequest;
use App\Http\Requests\V1\Admin\College\UpdateCollegeRequest;
use App\Http\Resources\College\CollegeResource;
use App\Models\College;
use App\Services\College\CollegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CollegeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view colleges', only: ['index', 'show', 'options']),
            new Middleware('permission:create colleges', only: ['store']),
            new Middleware('permission:edit colleges', only: ['update']),
            new Middleware('permission:delete colleges', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly CollegeService $collegeService,
    ) {}

    public function index(IndexCollegeRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            CollegeResource::collection($this->collegeService->paginate($request->filters())),
            'Colleges retrieved successfully.',
        );
    }

    public function options(): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->collegeService->options(),
            'Colleges retrieved successfully.',
        );
    }

    public function store(StoreCollegeRequest $request): JsonResponse
    {
        $college = $this->collegeService->create($request->validated());

        activity()->performedOn($college)->log('College created.');

        return ApiResponse::respondWithResource(
            new CollegeResource($college),
            'College created successfully.',
            201,
        );
    }

    public function show(College $college): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new CollegeResource($college->loadCount(['students', 'teachers'])),
            'College retrieved successfully.',
        );
    }

    public function update(UpdateCollegeRequest $request, College $college): JsonResponse
    {
        $college = $this->collegeService->update($college, $request->validated());

        activity()->performedOn($college)->log('College updated.');

        return ApiResponse::respondWithResource(
            new CollegeResource($college),
            'College updated successfully.',
        );
    }

    public function destroy(College $college): JsonResponse
    {
        activity()->performedOn($college)->log('College deleted.');

        $this->collegeService->delete($college);

        return ApiResponse::respondSuccess('College deleted successfully.');
    }
}
