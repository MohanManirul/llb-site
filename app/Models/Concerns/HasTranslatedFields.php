<?php

namespace App\Models\Concerns;

trait HasTranslatedFields
{
    /**
     * @return array{bn: ?string, en: ?string}
     */
    public function translated(string $field, bool $fallback = true): array
    {
        $bn = $this->{$field.'_bn'};
        $en = $this->{$field.'_en'};

        return [
            'bn' => $bn ?? ($fallback ? $en : null),
            'en' => $en ?? ($fallback ? $bn : null),
        ];
    }
}
