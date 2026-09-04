<?php

namespace App\Services\PublicApi;

use App\DTOs\FilterData;
use App\Models\StudyMaterial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class PublicMaterialService
{
    /**
     * @return LengthAwarePaginator<int, StudyMaterial>
     */
    public function paginate(FilterData $filters): LengthAwarePaginator
    {
        return StudyMaterial::query()
            ->publiclyVisible()
            ->with([
                'subject:id,slug,name_bn,name_en,program_id,program_level_id',
                'subject.program:id,slug,name_bn,name_en,short_name_bn,short_name_en',
                'session:id,slug,label',
            ])
            ->withCount('files')
            ->searchable($filters->search, [
                'title_bn', 'title_en', 'description_bn', 'description_en',
                'author', 'subject.name_bn', 'subject.name_en',
            ])
            ->filterable($filters->only([
                'type', 'exam_stage', 'exam_year', 'content_language',
                'subject' => 'subject.slug',
            ]))
            ->when($filters->filter('program'), fn ($query, $slug) => $query
                ->whereHas('subject.program', fn ($q) => $q->where('slug', $slug)))
            ->when($filters->filter('level'), fn ($query, $slug) => $query
                ->whereHas('subject.level', fn ($q) => $q->where('slug', $slug)))
            ->when($filters->filter('session'), fn ($query, $slug) => $query
                ->whereHas('session', fn ($q) => $q->where('slug', $slug)))
            ->when($filters->filter('featured'), fn ($query) => $query->where('is_featured', true))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->orderByDesc('id')
            ->paginate($filters->perPage);
    }

    public function show(StudyMaterial $material): StudyMaterial
    {
        StudyMaterial::whereKey($material->id)->increment('view_count');

        return $material->load([
            'subject:id,slug,name_bn,name_en,program_id,program_level_id',
            'subject.program:id,slug,name_bn,name_en,short_name_bn,short_name_en,has_levels,level_label_bn,level_label_en',
            'subject.level:id,slug,name_bn,name_en',
            'session:id,slug,label',
            'files',
        ]);
    }

    /**
     * @return Collection<int, StudyMaterial>
     */
    public function related(StudyMaterial $material, int $limit = 6)
    {
        return StudyMaterial::query()
            ->publiclyVisible()
            ->whereKeyNot($material->id)
            ->where('subject_id', $material->subject_id)
            ->with(['subject:id,slug,name_bn,name_en', 'session:id,slug,label'])
            ->withCount('files')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }
}
