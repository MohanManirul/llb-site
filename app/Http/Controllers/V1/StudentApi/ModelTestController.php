<?php

namespace App\Http\Controllers\V1\StudentApi;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StudentApi\IndexStudentModelTestRequest;
use App\Http\Resources\StudentApi\StudentModelTestResource;
use App\Models\ModelTest;
use App\Services\StudentApi\StudentModelTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModelTestController extends Controller
{
    public function __construct(
        private readonly StudentModelTestService $studentModelTestService,
    ) {}

    public function index(IndexStudentModelTestRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            StudentModelTestResource::collection(
                $this->studentModelTestService->paginate($request->user('student'), $request->filters()),
            ),
            'Model tests retrieved successfully.',
        );
    }

    public function show(Request $request, ModelTest $modelTest): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new StudentModelTestResource(
                $this->studentModelTestService->show($modelTest, $request->user('student')),
            ),
            'Model test retrieved successfully.',
        );
    }
}
