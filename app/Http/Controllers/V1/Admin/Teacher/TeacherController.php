<?php

namespace App\Http\Controllers\V1\Admin\Teacher;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Teacher\IndexTeacherRequest;
use App\Http\Resources\Teacher\TeacherResource;
use App\Models\Teacher;
use App\Services\Teacher\TeacherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TeacherController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view teachers', only: ['index', 'show']),
            new Middleware('permission:edit teachers', only: ['toggleActive']),
        ];
    }

    public function __construct(
        private readonly TeacherService $teacherService,
    ) {}

    public function index(IndexTeacherRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            TeacherResource::collection($this->teacherService->paginate($request->filters())),
            'Teachers retrieved successfully.',
        );
    }

    public function show(Teacher $teacher): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new TeacherResource($this->teacherService->show($teacher)),
            'Teacher retrieved successfully.',
        );
    }

    public function toggleActive(Request $request, Teacher $teacher): JsonResponse
    {
        $teacher = $this->teacherService->toggleActive($teacher, $request->user()?->id);

        activity()->performedOn($teacher)->log($teacher->is_active ? 'Teacher approved.' : 'Teacher deactivated.');

        return ApiResponse::respondWithResource(
            new TeacherResource($this->teacherService->show($teacher)),
            $teacher->is_active ? 'Teacher approved successfully.' : 'Teacher deactivated successfully.',
        );
    }
}
