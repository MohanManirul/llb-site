<?php

namespace App\Http\Controllers\V1\PublicApi;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\PublicApi\IndexPublicMaterialRequest;
use App\Http\Resources\PublicApi\PublicMaterialDetailResource;
use App\Http\Resources\PublicApi\PublicMaterialResource;
use App\Models\StudyMaterial;
use App\Services\PublicApi\PublicMaterialService;
use Illuminate\Http\JsonResponse;

class MaterialController extends Controller
{
    public function __construct(
        private readonly PublicMaterialService $publicMaterialService,
    ) {}

    public function index(IndexPublicMaterialRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            PublicMaterialResource::collection(
                $this->publicMaterialService->paginate($request->filters()),
            ),
            'Materials retrieved successfully.',
        );
    }

    public function show(StudyMaterial $studyMaterial): JsonResponse
    {
        abort_unless($studyMaterial->isPubliclyVisible(), 404);

        return ApiResponse::respondWithResource(
            new PublicMaterialDetailResource($this->publicMaterialService->show($studyMaterial)),
            'Material retrieved successfully.',
            additional: [
                'related' => PublicMaterialResource::collection(
                    $this->publicMaterialService->related($studyMaterial),
                )->resolve(),
            ],
        );
    }
}
