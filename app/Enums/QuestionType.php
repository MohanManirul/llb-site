<?php

namespace App\Enums;

enum QuestionType: string
{
    case Mcq = 'mcq';
    case Written = 'written';

    /**
     * @return array<int, array{value: string, label_bn: string, label_en: string}>
     */
    public static function labels(): array
    {
        return [
            ['value' => self::Mcq->value, 'label_bn' => 'এমসিকিউ', 'label_en' => 'MCQ'],
            ['value' => self::Written->value, 'label_bn' => 'লিখিত', 'label_en' => 'Written'],
        ];
    }
}
