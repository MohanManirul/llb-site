<?php

namespace App\Services\PublicApi;

use App\Models\College;
use Illuminate\Support\Collection;

final class PublicCollegeService
{
    /**
     * @return Collection<int, College>
     */
    public function list(?string $search = null): Collection
    {
        return College::query()
            ->where('is_active', true)
            ->searchable($search, ['name_bn', 'name_en', 'short_name_bn', 'short_name_en'])
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get();
    }
}
