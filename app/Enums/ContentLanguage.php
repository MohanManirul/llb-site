<?php

namespace App\Enums;

enum ContentLanguage: string
{
    case Bangla = 'bn';
    case English = 'en';
    case Both = 'both';

    /**
     * @return array<int, array{value: string, label_bn: string, label_en: string}>
     */
    public static function labels(): array
    {
        return [
            ['value' => self::Bangla->value, 'label_bn' => 'বাংলা', 'label_en' => 'Bangla'],
            ['value' => self::English->value, 'label_bn' => 'ইংরেজি', 'label_en' => 'English'],
            ['value' => self::Both->value, 'label_bn' => 'বাংলা ও ইংরেজি', 'label_en' => 'Bangla & English'],
        ];
    }
}
