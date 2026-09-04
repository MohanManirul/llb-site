<?php

use App\Support\ActivityLog;
use App\Utilities\Asset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

if (! function_exists('activity')) {
    function activity(): ActivityLog
    {
        return new ActivityLog;
    }
}

if (! function_exists('getThumbnailPath')) {
    function getThumbnailPath(?string $originalPath): string
    {
        return Asset::getThumbnailPath($originalPath);
    }
}

if (! function_exists('existsAssetUrl')) {
    function existsAssetUrl(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        return Cache::rememberForever('asset:exists:'.md5($path), function () use ($path) {
            return @Storage::disk(config('filesystems.default'))->exists($path);
        });
    }
}

if (! function_exists('assetUrl')) {
    function assetUrl(?string $path): string
    {
        if (existsAssetUrl($path)) {
            return Storage::disk(config('filesystems.default'))->url($path);
        }

        return '';
    }
}
