<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class Slug
{
    /**
     * @param  class-string<Model>  $model
     * @param  array<int, string>  $suffixes
     */
    public static function for(
        string $model,
        ?string $source,
        ?int $ignoreId = null,
        string $fallbackPrefix = 'item',
        array $suffixes = [],
    ): string {
        $base = Str::slug(Str::ascii(trim((string) $source)));

        if ($base === '') {
            $base = $fallbackPrefix.'-'.Str::lower(Str::random(8));
        }

        $base = Str::limit($base, 130, '');

        if (self::isFree($model, $base, $ignoreId)) {
            return $base;
        }

        foreach ($suffixes as $suffix) {
            $candidate = $base.'-'.Str::slug(Str::ascii($suffix));

            if (self::isFree($model, $candidate, $ignoreId)) {
                return $candidate;
            }
        }

        for ($i = 2; ; $i++) {
            $candidate = $base.'-'.$i;

            if (self::isFree($model, $candidate, $ignoreId)) {
                return $candidate;
            }
        }
    }

    /**
     * @param  class-string<Model>  $model
     */
    private static function isFree(string $model, string $slug, ?int $ignoreId): bool
    {
        $query = $model::query()->where('slug', $slug);

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $query->withTrashed();
        }

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return ! $query->exists();
    }
}
