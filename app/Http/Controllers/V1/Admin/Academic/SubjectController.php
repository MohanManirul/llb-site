<?php

namespace App\Http\Controllers\V1\Admin\Academic;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Academic\IndexSubjectRequest;
use App\Http\Requests\V1\Admin\Academic\StoreSubjectRequest;
use App\Http\Requests\V1\Admin\Academic\UpdateSubjectRequest;
use App\Http\Resources\Academic\SubjectResource;
use App\Models\Subject;
use App\Services\Academic\SubjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SubjectController extends Controller implements HasMiddleware
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
        private readonly SubjectService $subjectService,
    ) {}

    public function index(IndexSubjectRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            SubjectResource::collection($this->subjectService->paginate($request->filters())),
            'Subjects retrieved successfully.',
        );
    }

    public function options(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->subjectService->options(
                $request->input('search'),
                $request->integer('program_id') ?: null,
                $request->integer('program_level_id') ?: null,
            ),
            'Subjects retrieved successfully.',
        );
    }

    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $subject = $this->subjectService->create($request->validated());

        activity()->performedOn($subject)->log('Subject created.');

        return ApiResponse::respondWithResource(
            new SubjectResource($subject),
            'Subject created successfully.',
            201,
        );
    }

    public function show(Subject $subject): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new SubjectResource($subject->load(['program', 'level'])),
            'Subject retrieved successfully.',
        );
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        $subject = $this->subjectService->update($subject, $request->validated());

        activity()->performedOn($subject)->log('Subject updated.');

        return ApiResponse::respondWithResource(
            new SubjectResource($subject),
            'Subject updated successfully.',
        );
    }

    public function destroy(Subject $subject): JsonResponse
    {
        activity()->performedOn($subject)->log('Subject deleted.');

        $this->subjectService->delete($subject);

        return ApiResponse::respondSuccess('Subject deleted successfully.');
    }
}
