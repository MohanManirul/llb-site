<?php

namespace App\Http\Controllers\V1\Admin\StudyMaterial;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\StudyMaterial\StoreMaterialFileRequest;
use App\Http\Resources\StudyMaterial\MaterialFileResource;
use App\Models\MaterialFile;
use App\Models\StudyMaterial;
use App\Services\StudyMaterial\MaterialFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MaterialFileController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view study materials', only: ['preview']),
            new Middleware('permission:edit study materials', only: ['store', 'reorder']),
            new Middleware('permission:delete study materials', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly MaterialFileService $materialFileService,
    ) {}

    public function store(StoreMaterialFileRequest $request, StudyMaterial $studyMaterial): JsonResponse
    {
        $file = $this->materialFileService->store(
            $studyMaterial,
            $request->file('file'),
            $request->safe()->except('file'),
        );

        activity()->performedOn($studyMaterial)->log('Material file added.');

        return ApiResponse::respondWithResource(
            new MaterialFileResource($file),
            'File uploaded successfully.',
            201,
        );
    }

    public function preview(StudyMaterial $studyMaterial, MaterialFile $file): Response
    {
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function reorder(Request $request, StudyMaterial $studyMaterial): JsonResponse
    {
        $validated = $request->validate([
            'file_ids' => ['required', 'array', 'min:1'],
            'file_ids.*' => ['integer'],
        ]);

        $this->materialFileService->reorder($studyMaterial, $validated['file_ids']);

        return ApiResponse::respondSuccess('Files reordered successfully.');
    }

    public function destroy(StudyMaterial $studyMaterial, MaterialFile $file): JsonResponse
    {
        if ($studyMaterial->isPubliclyVisible() && $studyMaterial->files()->count() === 1) {
            return ApiResponse::respondError(
                'A published material must keep at least one file. Unpublish it first.',
                422,
            );
        }

        activity()->performedOn($studyMaterial)->log('Material file removed.');

        $this->materialFileService->delete($file);

        return ApiResponse::respondSuccess('File deleted successfully.');
    }
}
