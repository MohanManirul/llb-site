<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\AcademicSession;
use App\Models\Notice;
use App\Models\Program;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\Models\User;
use App\Support\DemoPdf;
use App\Support\Slug;
use App\Utilities\Asset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Sample study materials (with real PDF blobs on the material disk) and
 * notices, so a fresh local install has something to browse, preview and
 * download. Idempotent — keyed on slug/title, re-running adds nothing.
 *
 * Demo data only: refuses to run in production.
 */
class DemoContentSeeder extends Seeder
{
    private const array MATERIALS = [
        [
            'program' => 'nu-llb-Pass', 'subject' => 'Law of Contract',
            'type' => 'suggestion', 'title_bn' => 'চুক্তি আইন সাজেশন ২০২৬', 'title_en' => 'Law of Contract Suggestion 2026',
            'description_bn' => 'বিগত বছরের প্রশ্ন বিশ্লেষণ করে সাজানো গুরুত্বপূর্ণ প্রশ্নের তালিকা।',
            'session' => '2025-26', 'exam_year' => 2026, 'status' => 'published', 'is_featured' => true,
            'files' => [['label_bn' => null, 'pages' => 12]],
        ],
        [
            'program' => 'nu-llb-Pass', 'subject' => 'Law of Contract',
            'type' => 'note', 'title_bn' => 'চুক্তি আইন ক্লাস নোট', 'title_en' => 'Law of Contract Class Notes',
            'description_bn' => 'ক্লাস লেকচারের পূর্ণাঙ্গ নোট, ধারা ধরে ধরে ব্যাখ্যা।',
            'session' => '2025-26', 'status' => 'published',
            'files' => [['label_bn' => null, 'pages' => 48]],
        ],
        [
            'program' => 'nu-llb-Pass', 'subject' => 'Law of Contract',
            'type' => 'book', 'title_bn' => 'চুক্তি আইন হ্যান্ডবুক', 'title_en' => 'Law of Contract Handbook',
            'author' => 'ড. মো. আবদুল হালিম', 'publisher' => 'ল বুক হাউস', 'edition' => '৫ম সংস্করণ',
            'status' => 'published',
            'files' => [
                ['label_bn' => '১ম খণ্ড', 'pages' => 220],
                ['label_bn' => '২য় খণ্ড', 'pages' => 198],
            ],
        ],
        [
            'program' => 'nu-llb-Pass', 'subject' => 'Jurisprudence',
            'type' => 'suggestion', 'title_bn' => 'আইনতত্ত্ব সাজেশন ২০২৬', 'title_en' => 'Jurisprudence Suggestion 2026',
            'session' => '2025-26', 'exam_year' => 2026, 'status' => 'published',
            'files' => [['label_bn' => null, 'pages' => 10]],
        ],
        [
            'program' => 'nu-llb-Pass', 'subject' => 'Muslim Law',
            'type' => 'note', 'title_bn' => 'মুসলিম আইন সংক্ষিপ্ত নোট', 'title_en' => 'Muslim Law Short Notes',
            'session' => '2025-26', 'status' => 'published',
            'files' => [['label_bn' => null, 'pages' => 36]],
        ],
        [
            'program' => 'nu-llb-Pass', 'subject' => 'Law of Evidence',
            'type' => 'suggestion', 'title_bn' => 'সাক্ষ্য আইন সাজেশন ২০২৬', 'title_en' => 'Law of Evidence Suggestion 2026',
            'session' => '2025-26', 'exam_year' => 2026, 'status' => 'published', 'is_featured' => true,
            'files' => [['label_bn' => null, 'pages' => 14]],
        ],
        [
            'program' => 'nu-llb-Pass', 'subject' => 'Code of Civil Procedure',
            'type' => 'book', 'title_bn' => 'দেওয়ানি কার্যবিধি ভাষ্য', 'title_en' => 'Commentary on the Code of Civil Procedure',
            'author' => 'বিচারপতি মো. হাসান', 'status' => 'published',
            'files' => [['label_bn' => null, 'pages' => 412]],
        ],
        [
            'program' => 'nu-llb-Pass', 'subject' => 'Penal Code',
            'type' => 'note', 'title_bn' => 'দণ্ডবিধি ধারাভিত্তিক নোট', 'title_en' => 'Penal Code Section-wise Notes',
            'session' => '2025-26', 'status' => 'published',
            'files' => [['label_bn' => null, 'pages' => 64]],
        ],
        [
            'program' => 'bar-council', 'subject' => 'Penal Code',
            'type' => 'suggestion', 'title_bn' => 'দণ্ডবিধি এমসিকিউ সাজেশন', 'title_en' => 'Penal Code MCQ Suggestion',
            'description_bn' => 'বার কাউন্সিল এমসিকিউ পরীক্ষার জন্য বাছাই করা ধারা ও প্রশ্ন।',
            'exam_stage' => 'mcq', 'exam_year' => 2026, 'status' => 'published', 'is_featured' => true,
            'files' => [['label_bn' => null, 'pages' => 22]],
        ],
        [
            'program' => 'bar-council', 'subject' => 'Law of Evidence',
            'type' => 'note', 'title_bn' => 'সাক্ষ্য আইন লিখিত পরীক্ষার নোট', 'title_en' => 'Law of Evidence Written Exam Notes',
            'exam_stage' => 'written', 'status' => 'published',
            'files' => [['label_bn' => null, 'pages' => 40]],
        ],
        [
            'program' => 'bar-council', 'subject' => 'Bar Council Order and Legal Ethics',
            'type' => 'suggestion', 'title_bn' => 'ভাইভা প্রস্তুতি ও পেশাগত আচরণ', 'title_en' => 'Viva Preparation and Legal Ethics',
            'exam_stage' => 'viva', 'status' => 'published',
            'files' => [['label_bn' => null, 'pages' => 16]],
        ],
        [
            'program' => 'bar-council', 'subject' => 'Limitation Act',
            'type' => 'suggestion', 'title_bn' => 'তামাদি আইন সাজেশন (পুরনো)', 'title_en' => 'Limitation Act Suggestion (Old)',
            'exam_stage' => 'mcq', 'exam_year' => 2024, 'status' => 'archived',
            'files' => [['label_bn' => null, 'pages' => 12]],
        ],
        [
            'program' => 'nu-llb-Pass', 'subject' => 'Hindu Law',
            'type' => 'suggestion', 'title_bn' => 'হিন্দু আইন সাজেশন (খসড়া)', 'title_en' => 'Hindu Law Suggestion (Draft)',
            'session' => '2025-26', 'exam_year' => 2026, 'status' => 'draft',
            'files' => [['label_bn' => null, 'pages' => 8]],
        ],
    ];

    private const array NOTICES = [
        [
            'title_bn' => '২০২৬ সালের এলএলবি (পাস) পরীক্ষার সময়সূচি প্রকাশ',
            'title_en' => 'LLB (Pass 2-Year-Term) Examination Schedule 2026',
            'excerpt_bn' => '১ম ও ২য় পর্বের পরীক্ষা শুরু হবে মার্চের প্রথম সপ্তাহে।',
            'body_bn' => "জাতীয় বিশ্ববিদ্যালয়ের ২০২৬ সালের এলএলবি (পাস) পরীক্ষার সময়সূচি প্রকাশিত হয়েছে।\n\n১ম পর্বের পরীক্ষা শুরু: মার্চের প্রথম সপ্তাহ\n২য় পর্বের পরীক্ষা শুরু: এপ্রিলের দ্বিতীয় সপ্তাহ\n\nবিস্তারিত সময়সূচি সংযুক্ত PDF-এ দেওয়া হলো। ফর্ম পূরণের শেষ তারিখ মনে রেখে প্রস্তুতি নিন।",
            'category' => 'exam', 'program' => 'nu-llb-Pass', 'session' => '2025-26',
            'is_pinned' => true, 'status' => 'published', 'attachment' => true,
        ],
        [
            'title_bn' => 'বার কাউন্সিল এনরোলমেন্ট এমসিকিউ পরীক্ষার তারিখ ঘোষণা',
            'title_en' => 'Bar Council Enrolment MCQ Exam Date Announced',
            'excerpt_bn' => 'এমসিকিউ পরীক্ষা অনুষ্ঠিত হবে আগামী মাসের শেষ শুক্রবার।',
            'body_bn' => "বাংলাদেশ বার কাউন্সিলের এনরোলমেন্ট এমসিকিউ পরীক্ষার তারিখ ঘোষণা করা হয়েছে।\n\nপ্রবেশপত্র পরীক্ষার সাত দিন আগে থেকে ডাউনলোড করা যাবে। কেন্দ্রের তালিকা পরে জানানো হবে।",
            'category' => 'exam', 'program' => 'bar-council',
            'is_pinned' => true, 'status' => 'published',
        ],
        [
            'title_bn' => '১ম পর্বের ক্লাস রুটিন (২০২৫-২৬ সেশন)',
            'title_en' => 'Class Routine for 1st Part (Session 2025-26)',
            'excerpt_bn' => 'নতুন সেশনের ক্লাস রুটিন সংযুক্ত করা হলো।',
            'body_bn' => "২০২৫-২৬ সেশনের ১ম পর্বের ক্লাস রুটিন সংযুক্ত PDF-এ দেওয়া হলো।\n\nক্লাস শুরু আগামী রবিবার থেকে। রুটিনে কোনো পরিবর্তন হলে এই নোটিশ হালনাগাদ করা হবে।",
            'category' => 'routine', 'program' => 'nu-llb-Pass', 'session' => '2025-26',
            'status' => 'published', 'attachment' => true,
        ],
        [
            'title_bn' => '২০২৫ সালের পরীক্ষার ফলাফল প্রকাশিত',
            'title_en' => 'Examination Results 2025 Published',
            'excerpt_bn' => 'ফলাফল জাতীয় বিশ্ববিদ্যালয়ের ওয়েবসাইটে পাওয়া যাচ্ছে।',
            'body_bn' => "২০২৫ সালের এলএলবি পরীক্ষার ফলাফল প্রকাশিত হয়েছে। জাতীয় বিশ্ববিদ্যালয়ের ওয়েবসাইটে রোল নম্বর দিয়ে ফলাফল দেখা যাবে।\n\nপুনঃনিরীক্ষণের আবেদন ফল প্রকাশের ৩০ দিনের মধ্যে করতে হবে।",
            'category' => 'result', 'status' => 'published',
        ],
        [
            'title_bn' => 'পুরনো ভর্তি বিজ্ঞপ্তি (মেয়াদোত্তীর্ণ)',
            'title_en' => 'Old Admission Notice (Expired)',
            'body_bn' => 'গত সেশনের ভর্তি কার্যক্রম শেষ হয়েছে। এই নোটিশটি সংরক্ষণের জন্য রাখা হলো।',
            'category' => 'admission', 'status' => 'published', 'expired' => true,
        ],
        [
            'title_bn' => 'নতুন সেবার ঘোষণা (খসড়া)',
            'title_en' => 'New Service Announcement (Draft)',
            'body_bn' => 'এই নোটিশটি এখনও প্রকাশ করা হয়নি — খসড়া অবস্থায় আছে।',
            'category' => 'general', 'status' => 'draft',
        ],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoContentSeeder is demo data only — skipped in production.');

            return;
        }

        $adminId = User::where('email', 'admin@gmail.com')->value('id');

        $created = ['materials' => 0, 'files' => 0, 'notices' => 0];

        foreach (self::MATERIALS as $definition) {
            $created = $this->seedMaterial($definition, $adminId, $created);
        }

        foreach (self::NOTICES as $definition) {
            $created['notices'] += $this->seedNotice($definition, $adminId);
        }

        $this->command?->info(sprintf(
            'Demo content in place (%d materials, %d files, %d notices added).',
            $created['materials'],
            $created['files'],
            $created['notices'],
        ));
    }

    /**
     * @param  array<string, int>  $created
     * @return array<string, int>
     */
    private function seedMaterial(array $definition, ?int $adminId, array $created): array
    {
        $subjectId = Subject::query()
            ->where('name_en', $definition['subject'])
            ->whereHas('program', fn ($query) => $query->where('slug', $definition['program']))
            ->value('id');

        if ($subjectId === null) {
            return $created;
        }

        if (StudyMaterial::withTrashed()->where('title_bn', $definition['title_bn'])->exists()) {
            return $created;
        }

        $status = ContentStatus::from($definition['status']);

        $material = StudyMaterial::create([
            'type' => $definition['type'],
            'slug' => Slug::for(StudyMaterial::class, $definition['title_en'], fallbackPrefix: $definition['type']),
            'title_bn' => $definition['title_bn'],
            'title_en' => $definition['title_en'],
            'description_bn' => $definition['description_bn'] ?? null,
            'subject_id' => $subjectId,
            'academic_session_id' => isset($definition['session'])
                ? AcademicSession::where('slug', $definition['session'])->value('id')
                : null,
            'exam_stage' => $definition['exam_stage'] ?? null,
            'exam_year' => $definition['exam_year'] ?? null,
            'content_language' => 'bn',
            'author' => $definition['author'] ?? null,
            'publisher' => $definition['publisher'] ?? null,
            'edition' => $definition['edition'] ?? null,
            'status' => $status,
            'published_at' => $status === ContentStatus::Draft ? null : now()->subDays(random_int(1, 45)),
            'is_featured' => $definition['is_featured'] ?? false,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);

        $created['materials']++;

        foreach (array_values($definition['files']) as $index => $file) {
            $this->storePdf(
                $material,
                $definition['title_en'].($file['label_bn'] !== null ? ' - Part '.($index + 1) : ''),
                $file['label_bn'],
                $file['pages'],
                $index + 1,
            );

            $created['files']++;
        }

        return $created;
    }

    private function storePdf(
        StudyMaterial $material,
        string $asciiTitle,
        ?string $labelBn,
        int $pageCount,
        int $sortOrder,
    ): void {
        $disk = (string) config('llb.material_disk');
        $fileName = str_replace(' ', '-', strtolower($asciiTitle)).'.pdf';

        $bytes = DemoPdf::generate($asciiTitle, [
            'Demo file seeded for local development.',
            'Material slug: '.$material->slug,
            'Pages in the real document: '.$pageCount,
        ]);

        $path = Asset::generateUploadPath($fileName, 'materials');

        Storage::disk($disk)->put($path, $bytes);

        $material->files()->create([
            'disk' => $disk,
            'path' => $path,
            'original_name' => $fileName,
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => strlen($bytes),
            'checksum' => hash('sha256', $bytes),
            'page_count' => $pageCount,
            'label_bn' => $labelBn,
            'sort_order' => $sortOrder,
        ]);
    }

    private function seedNotice(array $definition, ?int $adminId): int
    {
        if (Notice::withTrashed()->where('title_bn', $definition['title_bn'])->exists()) {
            return 0;
        }

        $status = ContentStatus::from($definition['status']);

        $attributes = [
            'slug' => Slug::for(Notice::class, $definition['title_en'], fallbackPrefix: 'notice'),
            'title_bn' => $definition['title_bn'],
            'title_en' => $definition['title_en'],
            'excerpt_bn' => $definition['excerpt_bn'] ?? null,
            'body_bn' => $definition['body_bn'],
            'category' => $definition['category'],
            'program_id' => isset($definition['program'])
                ? Program::where('slug', $definition['program'])->value('id')
                : null,
            'academic_session_id' => isset($definition['session'])
                ? AcademicSession::where('slug', $definition['session'])->value('id')
                : null,
            'is_pinned' => $definition['is_pinned'] ?? false,
            'status' => $status,
            'published_at' => $status === ContentStatus::Draft ? null : now()->subDays(random_int(1, 20)),
            'expires_at' => ($definition['expired'] ?? false) ? now()->subDay() : null,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ];

        if ($definition['attachment'] ?? false) {
            $disk = (string) config('llb.material_disk');
            $bytes = DemoPdf::generate($definition['title_en'], ['Demo attachment seeded for local development.']);
            $path = Asset::generateUploadPath('attachment.pdf', 'notices');

            Storage::disk($disk)->put($path, $bytes);

            $attributes['attachment_disk'] = $disk;
            $attributes['attachment_path'] = $path;
            $attributes['attachment_name'] = 'attachment.pdf';
            $attributes['attachment_size'] = strlen($bytes);
        }

        Notice::create($attributes);

        return 1;
    }
}
