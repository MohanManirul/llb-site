<?php

namespace App\Support;

final class Phone
{
    /**
     * Bangladeshi mobile numbers arrive as 01712345678, +8801712345678 or
     * 8801712345678, often with spaces or dashes. Logging in by mobile only
     * works if every form of the same number is stored identically, so all of
     * them collapse to the local 11-digit form before validation or lookup.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '880')) {
            $digits = '0'.substr($digits, 3);
        } elseif (str_starts_with($digits, '1') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    public const RULE = 'regex:/^01[3-9][0-9]{8}$/';
}
