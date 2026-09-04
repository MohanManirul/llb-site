<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    public function scopeSearchable(Builder $query, ?string $searchTerm, array $attributes): Builder
    {
        $searchTerm = trim((string) $searchTerm);

        if ($searchTerm === '' || $attributes === []) {
            return $query;
        }

        return $query->where(function (Builder $searchQuery) use ($searchTerm, $attributes): void {
            foreach ($attributes as $attribute) {
                if (str_contains($attribute, '.')) {
                    $lastDot = strrpos($attribute, '.');
                    $relation = substr($attribute, 0, $lastDot);
                    $column = substr($attribute, $lastDot + 1);

                    $searchQuery->orWhereHas(
                        $relation,
                        fn (Builder $relationQuery) => $relationQuery->whereLike($column, "%{$searchTerm}%"),
                    );

                    continue;
                }

                $searchQuery->orWhereLike($attribute, "%{$searchTerm}%");
            }
        });
    }

    public function scopeFilterable(Builder $query, array $filters): Builder
    {
        foreach ($filters as $attribute => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (str_contains($attribute, '.')) {
                $lastDot = strrpos($attribute, '.');
                $relation = substr($attribute, 0, $lastDot);
                $column = substr($attribute, $lastDot + 1);

                $query->whereHas($relation, function (Builder $relationQuery) use ($column, $value): void {
                    is_array($value)
                        ? $relationQuery->whereIn($column, $value)
                        : $relationQuery->where($column, $value);
                });

                continue;
            }

            is_array($value)
                ? $query->whereIn($attribute, $value)
                : $query->where($attribute, $value);
        }

        return $query;
    }
}
