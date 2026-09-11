<?php

namespace App\Services\TeacherApi;

use App\Models\ClassNote;

final class TeacherClassNoteService extends CollegeContentService
{
    protected function model(): string
    {
        return ClassNote::class;
    }

    protected function uploadFolder(): string
    {
        return 'class-notes';
    }

    /**
     * @return array<int, string>
     */
    protected function filterable(): array
    {
        return ['status', 'subject_id'];
    }

    /**
     * @return array<int, string>
     */
    protected function relations(): array
    {
        return ['session:id,label', 'subject:id,name_bn,name_en'];
    }
}
