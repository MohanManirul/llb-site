<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable([
    'name_bn', 'name_en', 'slogan_bn', 'slogan_en', 'logo', 'favicon',
    'whatsapp_url', 'facebook_url', 'email', 'phone',
])]
class SiteSetting extends Model
{
    use HasTranslatedFields;

    public const CACHE_KEY = 'site-settings';

    public const ROW_ID = 1;

    /**
     * The one row every request reads — header, footer and the blade shell all
     * need it, so it is cached until an admin saves the form.
     *
     * The cache holds the raw attributes, never the model: a serialized model
     * comes back as __PHP_Incomplete_Class wherever the class is not loadable
     * at unserialize time, and an array is immune to that.
     */
    public static function current(): self
    {
        try {
            $attributes = Cache::rememberForever(self::CACHE_KEY, fn () => self::freshAttributes());

            if (! is_array($attributes)) {
                // A value written by a build that cached the model itself.
                Cache::forget(self::CACHE_KEY);
                $attributes = self::freshAttributes();
                Cache::forever(self::CACHE_KEY, $attributes);
            }
        } catch (QueryException) {
            // The blade shell reads this on every response, including the ones
            // served between a deploy and its migration.
            return new self;
        }

        return $attributes === [] ? new self : (new self)->newFromBuilder($attributes);
    }

    /**
     * @return array<string, mixed>
     */
    private static function freshAttributes(): array
    {
        return self::query()->find(self::ROW_ID)?->getAttributes() ?? [];
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Uploaded images live on the storage disk; the values seeded with this
     * feature are files shipped in public/, which are already absolute paths.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->resolveAssetUrl($this->logo);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->resolveAssetUrl($this->favicon);
    }

    private function resolveAssetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, '/') || str_starts_with($path, 'http')
            ? $path
            : assetUrl($path);
    }
}
