<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\Program;
use App\Models\StudyMaterial;
use App\Models\Subject;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function home(string $locale): Response
    {
        return Inertia::render('public/home/page');
    }

    public function program(string $locale, string $program): Response
    {
        $meta = Program::query()
            ->where('slug', $program)
            ->where('is_active', true)
            ->first(['slug', 'name_bn', 'name_en']);

        abort_unless($meta !== null, 404);

        return Inertia::render('public/programs/show/page', [
            'programSlug' => $program,
            'meta' => ['title_bn' => $meta->name_bn, 'title_en' => $meta->name_en],
        ]);
    }

    public function browse(string $locale, ?string $type = null): Response
    {
        return Inertia::render('public/browse/page', [
            'pinnedType' => $type,
        ]);
    }

    public function subject(string $locale, string $subject): Response
    {
        $meta = Subject::query()
            ->where('slug', $subject)
            ->where('is_active', true)
            ->first(['slug', 'name_bn', 'name_en', 'description_bn', 'description_en']);

        abort_unless($meta !== null, 404);

        return Inertia::render('public/subjects/show/page', [
            'subjectSlug' => $subject,
            'meta' => [
                'title_bn' => $meta->name_bn,
                'title_en' => $meta->name_en,
                'description_bn' => $meta->description_bn,
                'description_en' => $meta->description_en,
            ],
        ]);
    }

    public function notices(): Response
    {
        return Inertia::render('public/notices/index/page');
    }

    public function notice(string $locale, string $notice): Response
    {
        $meta = Notice::query()
            ->publiclyVisible()
            ->where('slug', $notice)
            ->first(['slug', 'title_bn', 'title_en', 'excerpt_bn', 'excerpt_en']);

        abort_unless($meta !== null, 404);

        return Inertia::render('public/notices/show/page', [
            'noticeSlug' => $notice,
            'meta' => [
                'title_bn' => $meta->title_bn,
                'title_en' => $meta->title_en,
                'description_bn' => $meta->excerpt_bn,
                'description_en' => $meta->excerpt_en,
            ],
        ]);
    }

    public function material(string $locale, string $material): Response
    {
        $meta = StudyMaterial::query()
            ->publiclyVisible()
            ->where('slug', $material)
            ->first(['slug', 'title_bn', 'title_en', 'description_bn', 'description_en']);

        abort_unless($meta !== null, 404);

        return Inertia::render('public/materials/show/page', [
            'materialSlug' => $material,
            'meta' => [
                'title_bn' => $meta->title_bn,
                'title_en' => $meta->title_en,
                'description_bn' => $meta->description_bn,
                'description_en' => $meta->description_en,
            ],
        ]);
    }
}
