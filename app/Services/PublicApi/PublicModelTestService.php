<?php

namespace App\Services\PublicApi;

use App\DTOs\FilterData;
use App\Models\ModelTest;
use Illuminate\Contracts\Pagination\Paginator;

final class PublicModelTestService
{
    /**
     * @return Paginator<int, ModelTest>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return ModelTest::query()
            ->publiclyVisible()
            ->with('program:id,slug,name_bn,name_en')
            ->withCount('questions')
            ->when($filters->filter('program'), fn ($query, $slug) => $query
                ->whereHas('program', fn ($q) => $q->where('slug', $slug)))
            ->orderByDesc('published_at')
            ->simplePaginate($filters->perPage);
    }

    public function show(ModelTest $modelTest): ModelTest
    {
        abort_unless($modelTest->isPubliclyVisible(), 404);

        return $modelTest
            ->load('program:id,slug,name_bn,name_en')
            ->loadCount('questions');
    }
}
