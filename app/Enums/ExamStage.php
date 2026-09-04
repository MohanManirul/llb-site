<?php

namespace App\Enums;

enum ExamStage: string
{
    case Preliminary = 'preliminary';
    case Mcq = 'mcq';
    case Written = 'written';
    case Viva = 'viva';

    /**
     * @return array<int, array{value: string, label_bn: string, label_en: string}>
     */
    public static function labels(): array
    {
        return [
            ['value' => self::Preliminary->value, 'label_bn' => 'প্রিলিমিনারি', 'label_en' => 'Preliminary'],
            ['value' => self::Mcq->value, 'label_bn' => 'এমসিকিউ', 'label_en' => 'MCQ'],
            ['value' => self::Written->value, 'label_bn' => 'লিখিত', 'label_en' => 'Written'],
            ['value' => self::Viva->value, 'label_bn' => 'ভাইভা', 'label_en' => 'Viva'],
        ];
    }
}
