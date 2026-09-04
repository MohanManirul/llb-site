<?php

namespace App\Enums;

enum NoticeCategory: string
{
    case General = 'general';
    case Exam = 'exam';
    case Routine = 'routine';
    case Result = 'result';
    case Admission = 'admission';

    /**
     * @return array<int, array{value: string, label_bn: string, label_en: string}>
     */
    public static function labels(): array
    {
        return [
            ['value' => self::General->value, 'label_bn' => 'সাধারণ', 'label_en' => 'General'],
            ['value' => self::Exam->value, 'label_bn' => 'পরীক্ষা', 'label_en' => 'Exam'],
            ['value' => self::Routine->value, 'label_bn' => 'রুটিন', 'label_en' => 'Routine'],
            ['value' => self::Result->value, 'label_bn' => 'ফলাফল', 'label_en' => 'Result'],
            ['value' => self::Admission->value, 'label_bn' => 'ভর্তি', 'label_en' => 'Admission'],
        ];
    }
}
