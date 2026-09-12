<?php

namespace App\Support;

final class Locale
{
    public static function resolve(?string $requested = null): string
    {
        return in_array($requested, config('llb.locales'), true)
            ? $requested
            : app()->getLocale();
    }
}
