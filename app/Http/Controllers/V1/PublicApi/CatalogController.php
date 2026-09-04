<?php

namespace App\Http\Controllers\V1\PublicApi;

use App\Enums\ContentLanguage;
use App\Enums\MaterialType;
use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicApi\PublicProgramResource;
use App\Http\Resources\PublicApi\PublicSubjectResource;
use App\Models\Program;
use App\Models\Subject;
use App\Services\PublicApi\PublicCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(
        private readonly PublicCatalogService $publicCatalogService,
    ) {}

    public function programs(): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            PublicProgramResource::collection($this->publicCatalogService->programs()),
            'Programs retrieved successfully.',
        );
    }

    public function program(Program $program): JsonResponse
    {
        abort_unless($program->is_active, 404);

        $program = $this->publicCatalogService->program($program);

        return ApiResponse::respondWithResource(
            new PublicProgramResource($program),
            'Program retrieved successfully.',
            additional: ['filters' => $this->publicCatalogService->filtersFor($program)],
        );
    }

    public function sessions(Request $request): JsonResponse
    {
        $sessions = $this->publicCatalogService->sessions();

        if ($request->boolean('current')) {
            $sessions = $sessions->where('is_current', true)->values();
        }

        return ApiResponse::respondWithSuccess(
            $sessions->map(fn ($session) => [
                'slug' => $session->slug,
                'label' => $session->label,
                'is_current' => $session->is_current,
            ]),
            'Sessions retrieved successfully.',
        );
    }

    public function subjects(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            PublicSubjectResource::collection($this->publicCatalogService->subjects(
                $request->input('program'),
                $request->input('level'),
                $request->input('search'),
            )),
            'Subjects retrieved successfully.',
        );
    }

    public function subject(Subject $subject): JsonResponse
    {
        abort_unless($subject->is_active, 404);

        return ApiResponse::respondWithResource(
            new PublicSubjectResource($this->publicCatalogService->subject($subject)),
            'Subject retrieved successfully.',
        );
    }

    public function filters(): JsonResponse
    {
        return ApiResponse::respondWithSuccess([
            'programs' => PublicProgramResource::collection(
                $this->publicCatalogService->programs()->load('levels'),
            ),
            'sessions' => $this->publicCatalogService->sessions()->map(fn ($session) => [
                'slug' => $session->slug,
                'label' => $session->label,
                'is_current' => $session->is_current,
            ]),
            'types' => MaterialType::labels(),
            'content_languages' => ContentLanguage::labels(),
        ], 'Filter options retrieved successfully.');
    }
}
