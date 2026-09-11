<?php

namespace App\Services\TeacherApi;

use App\Models\Notice;
use App\Models\Teacher;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

final class TeacherNoticeService extends CollegeContentService
{
    protected function model(): string
    {
        return Notice::class;
    }

    protected function uploadFolder(): string
    {
        return 'notices';
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['title_bn', 'title_en', 'body_bn', 'body_en'];
    }

    /**
     * @return array<int, string>
     */
    protected function filterable(): array
    {
        return ['status', 'category'];
    }

    public function create(Teacher $teacher, array $data, ?UploadedFile $attachment): Model
    {
        $data['slug'] = Slug::for(
            Notice::class,
            ($data['title_en'] ?? null) ?: $data['title_bn'],
            fallbackPrefix: 'notice',
        );

        return parent::create($teacher, $data, $attachment);
    }
}
