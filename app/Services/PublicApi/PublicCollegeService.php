<?php

namespace App\Services\PublicApi;

use App\Models\College;
use Collator;
use Illuminate\Support\Collection;

final class PublicCollegeService
{
    /**
     * @return Collection<int, College>
     */
    public function list(?string $search = null, string $locale = 'en'): Collection
    {
        $colleges = College::query()
            ->where('is_active', true)
            ->searchable($search, ['name_bn', 'name_en', 'short_name_bn', 'short_name_en'])
            ->get();

        $collator = $this->collator($locale);

        return $colleges
            ->sort(fn (College $a, College $b) => $this->compare(
                $this->sortableName($a, $locale),
                $this->sortableName($b, $locale),
                $collator,
            ))
            ->values();
    }

    private function collator(string $locale): ?Collator
    {
        if (! class_exists(Collator::class)) {
            return null;
        }

        return new Collator($locale === 'bn' ? 'bn_BD' : 'en_US');
    }

    private function compare(string $left, string $right, ?Collator $collator): int
    {
        if ($collator === null) {
            return strcmp($left, $right);
        }

        return (int) $collator->compare($left, $right);
    }

    private function sortableName(College $college, string $locale): string
    {
        return trim((string) ($locale === 'bn'
            ? $college->name_bn ?? $college->name_en
            : $college->name_en ?? $college->name_bn));
    }
}
