<?php

namespace App\Http\Resources\PublicApi;

use App\Models\MaterialFile;
use Illuminate\Http\Request;

class PublicMaterialDetailResource extends PublicMaterialResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'files' => $this->whenLoaded('files', fn () => $this->files->map(
                fn (MaterialFile $file) => [
                    'id' => $file->id,
                    'label' => $file->translated('label', false),
                    'size' => $file->size,
                    'page_count' => $file->page_count,
                    'download_count' => $file->download_count,
                    'preview_url' => route('v1.public.materials.files.preview', [
                        'studyMaterial' => $this->slug,
                        'file' => $file->id,
                    ]),
                    'download_url' => route('v1.public.materials.files.download', [
                        'studyMaterial' => $this->slug,
                        'file' => $file->id,
                    ]),
                ],
            )),
        ];
    }
}
