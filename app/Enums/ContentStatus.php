<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * @return array<int, array{value: string, label_bn: string, label_en: string}>
     */
    public static function labels(): array
    {
        return [
            ['value' => self::Draft->value, 'label_bn' => 'খসড়া', 'label_en' => 'Draft'],
            ['value' => self::Published->value, 'label_bn' => 'প্রকাশিত', 'label_en' => 'Published'],
            ['value' => self::Archived->value, 'label_bn' => 'আর্কাইভ', 'label_en' => 'Archived'],
        ];
    }
}
