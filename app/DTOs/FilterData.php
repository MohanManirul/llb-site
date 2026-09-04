<?php

namespace App\DTOs;

final readonly class FilterData
{
    /**
     * @param  array<string, mixed>  $filters  validated endpoint-specific filters
     */
    public function __construct(
        public ?string $search,
        public string $sortBy,
        public string $sortDir,
        public int $page,
        public int $perPage,
        public array $filters = [],
    ) {}

    public function filter(string $key, mixed $default = null): mixed
    {
        $value = $this->filters[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public function hasFilter(string $key): bool
    {
        return $this->filter($key) !== null;
    }

    /**
     * @param  array<int|string, string>  $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $out = [];

        foreach ($keys as $key => $column) {
            $key = is_int($key) ? $column : $key;
            $out[$column] = $this->filter($key);
        }

        return $out;
    }
}
