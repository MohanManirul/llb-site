<?php

namespace App\Services\TeacherApi;

use App\Models\ClassRoutine;

final class TeacherClassRoutineService extends CollegeContentService
{
    protected function model(): string
    {
        return ClassRoutine::class;
    }

    protected function uploadFolder(): string
    {
        return 'class-routines';
    }
}
