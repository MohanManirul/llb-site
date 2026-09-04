<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Translation\PotentiallyTranslatedString;

class UniquePhone implements ValidationRule
{
    public function __construct(
        protected Model $model,
        protected ?string $ignoreId = null
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $formatPhone = formatPhoneNumber($value);

        $isExists = $this->model->where('phone', $formatPhone)
            ->when($this->ignoreId, function ($query) {
                $query->where('id', '!=', $this->ignoreId);
            })->exists();

        if ($isExists) {
            $fail(__('The :attribute has already been taken.', ['attribute' => $attribute]));
        }
    }
}
