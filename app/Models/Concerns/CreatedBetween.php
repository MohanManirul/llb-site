<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait CreatedBetween
{
    public function scopeCreatedBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when(
                $from !== null,
                fn (Builder $scoped) => $scoped->whereDate('created_at', '>=', $from),
            )
            ->when(
                $to !== null,
                fn (Builder $scoped) => $scoped->whereDate('created_at', '<=', $to),
            );
    }
}
