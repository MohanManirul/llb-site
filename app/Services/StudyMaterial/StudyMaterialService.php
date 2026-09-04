<?php

namespace App\Services\StudyMaterial;

use App\DTOs\FilterData;
use App\Enums\ContentStatus;
use App\Models\StudyMaterial;
use App\Support\Slug;
use App\Utilities\Asset;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StudyMaterialService
{
    public function __construct(
        private readonly MaterialFileService $materialFileService,
    ) {}

    /**
     * @return Paginator<int, StudyMaterial>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return StudyMaterial::query()
            ->with([
                'subject:id,name_bn,name_en,program_id,program_level_id',
                'subject.program:id,name_bn,name_en,slug',
                'session:id,label',
            ])
            ->withCount('files')
            ->searchable($filters->search, [
                'title_bn', 'title_en', 'author', 'subject.name_bn', 'subject.name_en',
            ])
            ->filterable($filters->only([
                'type', 'status', 'subject_id', 'academic_session_id',
                'exam_stage', 'content_language',
            ]))
            ->when($filters->filter('program_id'), fn ($query, $programId) => $query
                ->whereHas('subject', fn ($q) => $q->where('program_id', $programId)))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $counts = [];

        foreach (ContentStatus::cases() as $status) {
            $counts[$status->value] = StudyMaterial::where('status', $status)->count();
        }

        return $counts;
    }

    /**
     * @param  array<int, array{file: UploadedFile, label_bn?: ?string, label_en?: ?string, page_count?: ?int}>  $files
     */
    public function create(array $data, array $files, ?int $userId = null): StudyMaterial
    {
        $data['slug'] = Slug::for(
            StudyMaterial::class,
            ($data['title_en'] ?? null) ?: $data['title_bn'],
            fallbackPrefix: $data['type'],
        );
        $data['status'] = ContentStatus::Draft;
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;

        $coverPath = $data['cover_image'] ?? null;
        $storedBlobs = [];

        try {
            $rows = [];

            foreach (array_values($files) as $index => $upload) {
                $attributes = $this->materialFileService->storeBlob($upload['file']);
                $storedBlobs[] = $attributes;

                $rows[] = [
                    ...$attributes,
                    'label_bn' => $upload['label_bn'] ?? null,
                    'label_en' => $upload['label_en'] ?? null,
                    'page_count' => $upload['page_count'] ?? null,
                    'sort_order' => $index + 1,
                ];
            }

            return DB::transaction(function () use ($data, $rows) {
                $material = StudyMaterial::create($data);

                foreach ($rows as $row) {
                    $material->files()->create($row);
                }

                return $material->load('files');
            });
        } catch (\Throwable $e) {
            foreach ($storedBlobs as $blob) {
                Asset::removeFile($blob['path'], $blob['disk']);
            }

            Asset::removeFile($coverPath);
            Asset::removeFile(getThumbnailPath($coverPath));

            throw $e;
        }
    }

    public function update(StudyMaterial $material, array $data, ?int $userId = null): StudyMaterial
    {
        unset($data['status'], $data['published_at']);

        $oldCover = $material->cover_image;
        $data['updated_by'] = $userId;

        try {
            $material->update($data);

            return $material->load('files');
        } catch (\Throwable $e) {
            if (($data['cover_image'] ?? null) !== $oldCover) {
                Asset::removeFile($data['cover_image'] ?? null);
                Asset::removeFile(getThumbnailPath($data['cover_image'] ?? null));
            }

            throw $e;
        }
    }

    public function publish(StudyMaterial $material, ?int $userId = null): StudyMaterial
    {
        if ($material->files()->count() === 0) {
            throw ValidationException::withMessages([
                'status' => 'A material cannot be published without at least one PDF file.',
            ]);
        }

        $material->update([
            'status' => ContentStatus::Published,
            'published_at' => $material->published_at ?? now(),
            'updated_by' => $userId,
        ]);

        return $material;
    }

    public function unpublish(StudyMaterial $material, ContentStatus $status, ?int $userId = null): StudyMaterial
    {
        $material->update([
            'status' => $status,
            'updated_by' => $userId,
        ]);

        return $material;
    }

    public function delete(StudyMaterial $material): void
    {
        $material->delete();
    }
}
