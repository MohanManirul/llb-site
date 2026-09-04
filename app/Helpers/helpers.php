<?php

use App\Support\ActivityLog;
use App\Utilities\Asset;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Propaganistas\LaravelPhone\PhoneNumber as Phone;

if (! function_exists('activity')) {
    function activity(): ActivityLog
    {
        return new ActivityLog;
    }
}

if (! function_exists('getDateFormat')) {
    function getDateFormat(?string $date): string
    {
        if (blank($date)) {
            return '';
        }

        return Carbon::parse($date)->format('d M Y');
    }
}

if (! function_exists('getDateTimeFormat')) {
    function getDateTimeFormat(?string $date): string
    {
        if (blank($date)) {
            return '';
        }

        return Str::upper(Carbon::parse($date)->tz(config('app.timezone'))->format('d M Y g:i A'));
    }
}

if (! function_exists('formatMoney')) {
    function formatMoney(float|int|string|null $amount): string
    {
        return config('currency.symbol')
            .config('currency.gap')
            .number_format((float) $amount, (int) config('currency.precision'));
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

if (! function_exists('assetPath')) {
    function assetPath(?string $path): string
    {
        if (existsAssetUrl($path)) {
            return Storage::disk(config('filesystems.default'))->path($path);
        }

        return '';
    }
}

if (! function_exists('thumbnailUrl')) {
    function thumbnailUrl(?string $path, ?string $imageType = null): string
    {
        return assetUrl(getThumbnailPath($path)) ?: getDefaultImageUrl($imageType);
    }
}

if (! function_exists('getDefaultImageUrl')) {
    function getDefaultImageUrl(?string $imageType = null): string
    {
        return match ($imageType) {
            'user' => asset('assets/images/default.png'),
            'client' => asset('assets/images/default.png'),
            'company' => asset('assets/images/default.png'),
            'employee' => asset('assets/images/default.png'),
            'project' => asset('assets/images/default.png'),
            default => asset('assets/images/default.png'),
        };
    }
}

if (! function_exists('badgeHtml')) {
    function badgeHtml(string $label, string $variant = 'default', ?string $additionalClasses = null): string
    {
        $baseClasses = 'inline-flex items-center px-2 py-0 rounded-full text-xs font-semibold uppercase border';

        $variantClasses = [
            'success' => 'bg-green-100 text-green-600 border-green-500',
            'error' => 'bg-red-100 text-red-500 border-red-500',
            'danger' => 'bg-red-100 text-red-500 border-red-500',
            'warning' => 'bg-yellow-100 text-yellow-800 border-yellow-600',
            'info' => 'bg-blue-100 text-blue-800 border-blue-500',
            'purple' => 'bg-purple-100 text-purple-500 border-purple-500',
            'pink' => 'bg-pink-100 text-pink-500 border-pink-500',
            'indigo' => 'bg-indigo-100 text-indigo-500 border-indigo-500',
            'default' => 'bg-gray-100 text-gray-700 border-gray-500',
            'primary' => 'bg-sky-100 text-sky-700 border-sky-500',
            'secondary' => 'bg-slate-100 text-slate-700 border-slate-500',
            'orange' => 'bg-orange-100 text-orange-700 border-orange-500',
            'teal' => 'bg-teal-100 text-teal-700 border-teal-500',
            'cyan' => 'bg-cyan-100 text-cyan-700 border-cyan-500',
            'emerald' => 'bg-emerald-100 text-emerald-700 border-emerald-500',
            'lime' => 'bg-lime-100 text-lime-700 border-lime-500',
            'amber' => 'bg-amber-100 text-amber-700 border-amber-500',
            'rose' => 'bg-rose-100 text-rose-700 border-rose-500',
            'violet' => 'bg-violet-100 text-violet-700 border-violet-500',
            'fuchsia' => 'bg-fuchsia-100 text-fuchsia-700 border-fuchsia-500',
            'stone' => 'bg-stone-100 text-stone-700 border-stone-500',
            'zinc' => 'bg-zinc-100 text-zinc-700 border-zinc-500',
            'neutral' => 'bg-neutral-100 text-neutral-700 border-neutral-500',
            'dark' => 'bg-gray-800 text-white border-gray-900',
        ];

        $variantKey = strtolower(trim($variant));
        $variantStyle = $variantClasses[$variantKey] ?? $variantClasses['default'];

        $classes = trim(implode(' ', array_filter([
            $baseClasses,
            $variantStyle,
            $additionalClasses,
        ])));

        return sprintf(
            '<span class="%s">%s</span>',
            htmlspecialchars($classes, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        );
    }
}

if (! function_exists('statusToTitle')) {
    function statusToTitle(?string $status): string
    {
        return $status ? Str::title(Str::replace(['-', '_'], ' ', $status)) : '';
    }
}

if (! function_exists('normalizePhoneNumber')) {
    function normalizePhoneNumber(string $phone): string
    {
        if (strlen($phone) === 10 && Str::startsWith($phone, '1')) {
            $phone = Str::start($phone, '880');
        } elseif (strlen($phone) === 11 && Str::startsWith($phone, '01')) {
            $phone = Str::start($phone, '88');
        } elseif (Str::startsWith($phone, '+88')) {
            $phone = Str::after($phone, '+');
        }

        return '+'.$phone;
    }
}

if (! function_exists('getLocalPhoneNumber')) {
    function getLocalPhoneNumber(?string $phoneNumber): ?string
    {
        if (blank($phoneNumber)) {
            return null;
        }

        $normalized = preg_replace('/[^\d+]/', '', $phoneNumber);

        $normalized = Str::of($normalized)
            ->replaceStart('+88', '')
            ->replaceStart('88', '')
            ->toString();

        return Str::length($normalized) === 11 ? $normalized : null;
    }
}

if (! function_exists('formatPhoneNumber')) {
    function formatPhoneNumber(?string $value): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        $value = bnToEnNumber($value);

        $phone = new Phone(Str::replace(['+', '-', ' '], '', $value));
        if (! $phone->isValid()) {
            $phone = normalizePhoneNumber((string) $phone);
            $phone = new Phone($phone);
            if (! $phone->isValid()) {
                return null;
            }
        }

        return str($phone->formatE164())->replace('+', '')->toString();
    }
}

if (! function_exists('bnToEnNumber')) {
    function bnToEnNumber(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Str::replace(
            ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $value
        );
    }
}
