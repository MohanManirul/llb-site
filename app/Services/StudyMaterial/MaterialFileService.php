<?php

namespace App\Services\StudyMaterial;

use App\Models\MaterialFile;
use App\Models\StudyMaterial;
use App\Utilities\Asset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class MaterialFileService
{
    /**
     * Writes the blob to the material disk and returns the row attributes for
     * it — the caller decides when (and inside which transaction) the row is
     * created, and owns removing the blob if that never happens.
     *
     * @return array<string, mixed>
     */
    public function storeBlob(UploadedFile $file): array
    {
        $disk = (string) config('llb.material_disk');

        $path = $file->storeAs(
            '',
            Asset::generateUploadPath($file->getClientOriginalName(), 'materials'),
            $disk,
        );

        return [
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'extension' => strtolower((string) $file->getClientOriginalExtension()),
            'mime_type' => (string) $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()) ?: null,
        ];
    }

    public function store(StudyMaterial $material, UploadedFile $file, array $meta = []): MaterialFile
    {
        $attributes = $this->storeBlob($file);

        try {
            return $material->files()->create([
                ...$attributes,
                'page_count' => $meta['page_count'] ?? null,
                'label_bn' => $meta['label_bn'] ?? null,
                'label_en' => $meta['label_en'] ?? null,
                'sort_order' => $meta['sort_order'] ?? ($material->files()->max('sort_order') + 1),
            ]);
        } catch (\Throwable $e) {
            Asset::removeFile($attributes['path'], $attributes['disk']);

            throw $e;
        }
    }

    public function delete(MaterialFile $file): void
    {
        $disk = $file->disk;
        $path = $file->path;

        $file->delete();

        Asset::removeFile($path, $disk);
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(StudyMaterial $material, array $orderedIds): void
    {
        DB::transaction(function () use ($material, $orderedIds) {
            foreach (array_values($orderedIds) as $index => $id) {
                $material->files()->whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });
    }
}
