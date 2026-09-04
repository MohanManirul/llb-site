<?php

namespace App\Enums;

enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Expired = 'expired';

    /**
     * @return array<int, array{value: string, label_bn: string, label_en: string}>
     */
    public static function labels(): array
    {
        return [
            ['value' => self::InProgress->value, 'label_bn' => 'চলমান', 'label_en' => 'In Progress'],
            ['value' => self::Submitted->value, 'label_bn' => 'জমা দেওয়া', 'label_en' => 'Submitted'],
            ['value' => self::Expired->value, 'label_bn' => 'মেয়াদোত্তীর্ণ', 'label_en' => 'Expired'],
        ];
    }
}
