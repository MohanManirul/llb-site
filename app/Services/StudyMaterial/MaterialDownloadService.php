<?php

namespace App\Services\StudyMaterial;

use App\Models\MaterialFile;
use App\Models\StudyMaterial;
use App\Services\Analytics\AnalyticsReportService;
use App\Services\Analytics\VisitorTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class MaterialDownloadService
{
    public function __construct(
        private readonly VisitorTrackingService $visitorTrackingService,
        private readonly AnalyticsReportService $analyticsReportService,
    ) {}

    public function download(Request $request, StudyMaterial $material, MaterialFile $file): Response
    {
        abort_unless($material->isPubliclyVisible(), 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        DB::transaction(function () use ($request, $material, $file) {
            MaterialFile::whereKey($file->id)->increment('download_count');
            StudyMaterial::whereKey($material->id)->increment('download_count');

            $this->analyticsReportService->record(
                $material,
                $file,
                $this->visitorTrackingService->visitorId($request),
                $this->visitorTrackingService->ipHash($request),
            );
        });

        if (config("filesystems.disks.{$file->disk}.driver") === 's3') {
            return redirect()->away(
                Storage::disk($file->disk)->temporaryUrl($file->path, now()->addMinutes(5)),
            );
        }

        return Storage::disk($file->disk)->download($file->path, $this->fileName($material, $file), [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function preview(StudyMaterial $material, MaterialFile $file): Response
    {
        abort_unless($material->isPubliclyVisible(), 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->response($file->path, $this->fileName($material, $file), [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function fileName(StudyMaterial $material, MaterialFile $file): string
    {
        $suffix = '';

        if ($material->files()->count() > 1) {
            $suffix = '-part-'.max(1, (int) $file->sort_order);
        }

        return $material->slug.$suffix.'.pdf';
    }
}
