<?php

namespace App\Http\Controllers\V1\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\MaterialFile;
use App\Models\StudyMaterial;
use App\Services\StudyMaterial\MaterialDownloadService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaterialFileController extends Controller
{
    public function __construct(
        private readonly MaterialDownloadService $materialDownloadService,
    ) {}

    public function preview(StudyMaterial $studyMaterial, MaterialFile $file): Response
    {
        return $this->materialDownloadService->preview($studyMaterial, $file);
    }

    public function download(Request $request, StudyMaterial $studyMaterial, MaterialFile $file): Response
    {
        return $this->materialDownloadService->download($request, $studyMaterial, $file);
    }
}
