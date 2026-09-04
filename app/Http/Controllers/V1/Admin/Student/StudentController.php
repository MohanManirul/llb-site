<?php

namespace App\Http\Controllers\V1\Admin\Student;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Student\IndexStudentRequest;
use App\Http\Resources\Student\StudentResource;
use App\Models\Student;
use App\Services\Student\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class StudentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view students', only: ['index', 'show']),
            new Middleware('permission:edit students', only: ['toggleActive']),
        ];
    }

    public function __construct(
        private readonly StudentService $studentService,
    ) {}

    public function index(IndexStudentRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            StudentResource::collection($this->studentService->paginate($request->filters())),
            'Students retrieved successfully.',
        );
    }

    public function show(Student $student): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new StudentResource($this->studentService->show($student)),
            'Student retrieved successfully.',
        );
    }

    public function toggleActive(Student $student): JsonResponse
    {
        $student = $this->studentService->toggleActive($student);

        activity()->performedOn($student)->log($student->is_active ? 'Student activated.' : 'Student deactivated.');

        return ApiResponse::respondWithResource(
            new StudentResource($student),
            'Student status updated successfully.',
        );
    }
}
