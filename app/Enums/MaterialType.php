<?php

namespace App\Enums;

enum MaterialType: string
{
    case Suggestion = 'suggestion';
    case Book = 'book';
    case Note = 'note';

    /**
     * @return array<int, array{value: string, label_bn: string, label_en: string}>
     */
    public static function labels(): array
    {
        return [
            ['value' => self::Suggestion->value, 'label_bn' => 'সাজেশন', 'label_en' => 'Suggestion'],
            ['value' => self::Book->value, 'label_bn' => 'বই', 'label_en' => 'Book'],
            ['value' => self::Note->value, 'label_bn' => 'ক্লাস নোট', 'label_en' => 'Class Note'],
        ];
    }
}
