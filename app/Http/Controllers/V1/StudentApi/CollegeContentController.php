<?php

namespace App\Http\Controllers\V1\StudentApi;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StudentApi\IndexCollegeContentRequest;
use App\Http\Resources\StudentApi\ClassNoteResource;
use App\Http\Resources\StudentApi\ClassRoutineResource;
use App\Http\Resources\StudentApi\CollegeNoticeResource;
use App\Models\ClassNote;
use App\Models\ClassRoutine;
use App\Models\Notice;
use App\Services\StudentApi\CollegeContentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CollegeContentController extends Controller
{
    public function __construct(
        private readonly CollegeContentService $collegeContentService,
    ) {}

    public function notices(IndexCollegeContentRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            CollegeNoticeResource::collection(
                $this->collegeContentService->notices($request->user('student'), $request->filters()),
            ),
            'College notices retrieved successfully.',
        );
    }

    public function notice(Request $request, Notice $notice): JsonResponse
    {
        $this->authorizeVisibility($request, $notice);

        return ApiResponse::respondWithResource(
            new CollegeNoticeResource($notice->load(['subject', 'session', 'teacher'])),
            'Notice retrieved successfully.',
        );
    }

    public function routines(IndexCollegeContentRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            ClassRoutineResource::collection(
                $this->collegeContentService->routines($request->user('student'), $request->filters()),
            ),
            'Class routines retrieved successfully.',
        );
    }

    public function routine(Request $request, ClassRoutine $classRoutine): JsonResponse
    {
        $this->authorizeVisibility($request, $classRoutine);

        return ApiResponse::respondWithResource(
            new ClassRoutineResource($classRoutine->load(['session', 'teacher'])),
            'Class routine retrieved successfully.',
        );
    }

    public function notes(IndexCollegeContentRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            ClassNoteResource::collection(
                $this->collegeContentService->notes($request->user('student'), $request->filters()),
            ),
            'Class notes retrieved successfully.',
        );
    }

    public function note(Request $request, ClassNote $classNote): JsonResponse
    {
        $this->authorizeVisibility($request, $classNote);

        return ApiResponse::respondWithResource(
            new ClassNoteResource($classNote->load(['subject', 'session', 'teacher'])),
            'Class note retrieved successfully.',
        );
    }

    public function noteFilters(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess([
            'subjects' => $this->collegeContentService->noteSubjects($request->user('student')),
        ], 'Filter options retrieved successfully.');
    }

    public function noticeAttachment(Request $request, Notice $notice): Response
    {
        $this->authorizeVisibility($request, $notice);

        return $this->streamAttachment($notice);
    }

    public function routineAttachment(Request $request, ClassRoutine $classRoutine): Response
    {
        $this->authorizeVisibility($request, $classRoutine);

        return $this->streamAttachment($classRoutine);
    }

    public function noteAttachment(Request $request, ClassNote $classNote): Response
    {
        $this->authorizeVisibility($request, $classNote);

        return $this->streamAttachment($classNote);
    }

    /**
     * A record belonging to another college is indistinguishable from one that
     * does not exist.
     */
    private function authorizeVisibility(Request $request, Model $record): void
    {
        $collegeId = $request->user('student')->college_id;

        abort_if($collegeId === null, 404);

        $visible = $record instanceof Notice
            ? $record->isVisibleToCollege($collegeId)
            : $record->isVisibleTo($collegeId);

        abort_unless($visible, 404);
    }

    private function streamAttachment(Model $record): Response
    {
        abort_unless($record->attachment_path !== null, 404);
        abort_unless(Storage::disk($record->attachment_disk)->exists($record->attachment_path), 404);

        $record->increment('attachment_download_count');

        return Storage::disk($record->attachment_disk)->response(
            $record->attachment_path,
            $record->attachment_name,
            ['Cache-Control' => 'private, no-store'],
        );
    }
}
