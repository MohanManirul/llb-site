<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class PhoneNumber implements ValidationRule
{
    public function __construct(private ?string $fieldTitle = null) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = formatPhoneNumber($value);
        if (is_null($value)) {
            $field = $this->fieldTitle ?? Str::replace('_', ' ', $attribute);
            $fail('The '.$field.' is not a valid phone number.');
        }
    }
}
