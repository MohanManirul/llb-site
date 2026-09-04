<?php

namespace App\Services\PublicApi;

use App\DTOs\FilterData;
use App\Models\Notice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class PublicNoticeService
{
    /**
     * @return LengthAwarePaginator<int, Notice>
     */
    public function paginate(FilterData $filters): LengthAwarePaginator
    {
        return Notice::query()
            ->publiclyVisible()
            ->unexpired()
            ->with([
                'program:id,slug,name_bn,name_en',
                'session:id,slug,label',
                'subject:id,slug,name_bn,name_en',
            ])
            ->searchable($filters->search, ['title_bn', 'title_en', 'body_bn', 'body_en'])
            ->filterable($filters->only(['category']))
            ->when($filters->filter('program'), fn (Builder $query, $slug) => $query
                ->where(fn (Builder $q) => $q
                    ->whereNull('program_id')
                    ->orWhereHas('program', fn (Builder $inner) => $inner->where('slug', $slug))))
            ->when($filters->filter('session'), fn (Builder $query, $slug) => $query
                ->where(fn (Builder $q) => $q
                    ->whereNull('academic_session_id')
                    ->orWhereHas('session', fn (Builder $inner) => $inner->where('slug', $slug))))
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->paginate($filters->perPage);
    }

    public function show(Notice $notice): Notice
    {
        return $notice->load([
            'program:id,slug,name_bn,name_en',
            'level:id,slug,name_bn,name_en',
            'session:id,slug,label',
            'subject:id,slug,name_bn,name_en',
        ]);
    }
}
