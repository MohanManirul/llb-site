<?php

namespace App\Rules\SalesReport;

use App\Models\SalesReport;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class NoOverlappingWeek implements ValidationRule
{
    public function __construct(
        private readonly int $projectId,
        private readonly ?string $weekEnd,
        private readonly ?int $ignoreId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value) || blank($this->weekEnd)) {
            return;
        }

        $overlapping = SalesReport::query()
            ->where('project_id', $this->projectId)
            ->when($this->ignoreId !== null, fn ($query) => $query->whereKeyNot($this->ignoreId))
            ->where('week_start', '<=', $this->weekEnd)
            ->where('week_end', '>=', $value)
            ->latestFirst()
            ->first();

        if ($overlapping === null) {
            return;
        }

        $fail(sprintf(
            'These dates overlap an existing report for this project (%s – %s).',
            $overlapping->week_start->toDateString(),
            $overlapping->week_end->toDateString(),
        ));
    }
}
