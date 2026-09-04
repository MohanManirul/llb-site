<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\Subject;
use App\Support\Slug;
use Illuminate\Database\Seeder;

/**
 * Production-safe like PermissionSeeder: additive only, keyed on natural
 * identifiers (slug, program+level+name), never sync, never truncate. Safe to
 * re-run against a live database.
 */
class AcademicStructureSeeder extends Seeder
{
    private const array PROGRAMS = [
        [
            'slug' => 'nu-llb-Pass',
            'name_bn' => 'এলএলবি (পাস)',
            'name_en' => 'LLB (Pass 2-Year-Term)',
            'short_name_bn' => 'পাস কোর্স',
            'short_name_en' => 'Pass',
            'has_levels' => true,
            'level_label_bn' => 'পর্ব',
            'level_label_en' => 'Part',
            'has_exam_stages' => false,
            'has_sessions' => true,
            'sort_order' => 1,
            'levels' => [
                ['position' => 1, 'slug' => '1st-part', 'name_bn' => '১ম পর্ব', 'name_en' => '1st Part'],
                ['position' => 2, 'slug' => '2nd-part', 'name_bn' => '২য় পর্ব', 'name_en' => '2nd Part'],
            ],
        ],
        [
            'slug' => 'llb-hons',
            'name_bn' => 'এলএলবি (অনার্স)',
            'name_en' => 'LLB (Hons)',
            'short_name_bn' => 'অনার্স',
            'short_name_en' => 'Hons',
            'has_levels' => true,
            'level_label_bn' => 'বর্ষ',
            'level_label_en' => 'Year',
            'has_exam_stages' => false,
            'has_sessions' => true,
            'sort_order' => 2,
            'levels' => [
                ['position' => 1, 'slug' => '1st-year', 'name_bn' => '১ম বর্ষ', 'name_en' => '1st Year'],
                ['position' => 2, 'slug' => '2nd-year', 'name_bn' => '২য় বর্ষ', 'name_en' => '2nd Year'],
                ['position' => 3, 'slug' => '3rd-year', 'name_bn' => '৩য় বর্ষ', 'name_en' => '3rd Year'],
                ['position' => 4, 'slug' => '4th-year', 'name_bn' => '৪র্থ বর্ষ', 'name_en' => '4th Year'],
            ],
        ],
        [
            'slug' => 'bar-council',
            'name_bn' => 'বার কাউন্সিল এনরোলমেন্ট',
            'name_en' => 'Bar Council Enrolment',
            'short_name_bn' => 'বার কাউন্সিল',
            'short_name_en' => 'Bar Council',
            'has_levels' => false,
            'level_label_bn' => null,
            'level_label_en' => null,
            'has_exam_stages' => true,
            'has_sessions' => false,
            'sort_order' => 3,
            'levels' => [],
        ],
        [
            'slug' => 'bjs',
            'name_bn' => 'বিজেএস (সহকারী জজ)',
            'name_en' => 'BJS (Assistant Judge)',
            'short_name_bn' => 'বিজেএস',
            'short_name_en' => 'BJS',
            'has_levels' => false,
            'level_label_bn' => null,
            'level_label_en' => null,
            'has_exam_stages' => true,
            'has_sessions' => false,
            'sort_order' => 4,
            'levels' => [],
        ],
        [
            'slug' => 'llm',
            'name_bn' => 'এলএলএম (মাস্টার্স)',
            'name_en' => 'LLM (Masters)',
            'short_name_bn' => 'এলএলএম',
            'short_name_en' => 'LLM',
            'has_levels' => true,
            'level_label_bn' => 'পর্ব',
            'level_label_en' => 'Part',
            'has_exam_stages' => false,
            'has_sessions' => true,
            'sort_order' => 5,
            'levels' => [
                ['position' => 1, 'slug' => '1st-part', 'name_bn' => '১ম পর্ব', 'name_en' => '1st Part'],
                ['position' => 2, 'slug' => '2nd-part', 'name_bn' => '২য় পর্ব', 'name_en' => '2nd Part'],
            ],
        ],
    ];

    private const array SUBJECTS = [
        'nu-llb-Pass' => [
            '1st-part' => [
                ['name_en' => 'Jurisprudence', 'name_bn' => 'আইনতত্ত্ব'],
                ['name_en' => 'Law of Contract', 'name_bn' => 'চুক্তি আইন'],
                ['name_en' => 'Law of Tort', 'name_bn' => 'টর্ট আইন'],
                ['name_en' => 'Constitutional Law of Bangladesh', 'name_bn' => 'বাংলাদেশের সাংবিধানিক আইন'],
                ['name_en' => 'Muslim Law', 'name_bn' => 'মুসলিম আইন'],
                ['name_en' => 'Hindu Law', 'name_bn' => 'হিন্দু আইন'],
                ['name_en' => 'Land Laws of Bangladesh', 'name_bn' => 'বাংলাদেশের ভূমি আইন'],
                ['name_en' => 'Equity and Trust', 'name_bn' => 'ইকুইটি ও ট্রাস্ট'],
            ],
            '2nd-part' => [
                ['name_en' => 'Code of Civil Procedure', 'name_bn' => 'দেওয়ানি কার্যবিধি'],
                ['name_en' => 'Code of Criminal Procedure', 'name_bn' => 'ফৌজদারি কার্যবিধি'],
                ['name_en' => 'Law of Evidence', 'name_bn' => 'সাক্ষ্য আইন'],
                ['name_en' => 'Penal Code', 'name_bn' => 'দণ্ডবিধি'],
                ['name_en' => 'Transfer of Property Act', 'name_bn' => 'সম্পত্তি হস্তান্তর আইন'],
                ['name_en' => 'Specific Relief Act', 'name_bn' => 'সুনির্দিষ্ট প্রতিকার আইন'],
                ['name_en' => 'Limitation Act', 'name_bn' => 'তামাদি আইন'],
                ['name_en' => 'Registration Act', 'name_bn' => 'রেজিস্ট্রেশন আইন'],
            ],
        ],
        'bar-council' => [
            '' => [
                ['name_en' => 'Code of Civil Procedure', 'name_bn' => 'দেওয়ানি কার্যবিধি'],
                ['name_en' => 'Code of Criminal Procedure', 'name_bn' => 'ফৌজদারি কার্যবিধি'],
                ['name_en' => 'Penal Code', 'name_bn' => 'দণ্ডবিধি'],
                ['name_en' => 'Law of Evidence', 'name_bn' => 'সাক্ষ্য আইন'],
                ['name_en' => 'Limitation Act', 'name_bn' => 'তামাদি আইন'],
                ['name_en' => 'Specific Relief Act', 'name_bn' => 'সুনির্দিষ্ট প্রতিকার আইন'],
                ['name_en' => 'Bar Council Order and Legal Ethics', 'name_bn' => 'বার কাউন্সিল আদেশ ও পেশাগত আচরণ'],
            ],
        ],
    ];

    public function run(): void
    {
        $created = ['programs' => 0, 'levels' => 0, 'sessions' => 0, 'subjects' => 0];

        foreach (self::PROGRAMS as $definition) {
            $levels = $definition['levels'];
            unset($definition['levels']);

            $program = Program::firstOrCreate(['slug' => $definition['slug']], $definition);
            $created['programs'] += (int) $program->wasRecentlyCreated;

            foreach ($levels as $index => $levelDefinition) {
                $level = ProgramLevel::firstOrCreate(
                    ['program_id' => $program->id, 'slug' => $levelDefinition['slug']],
                    [...$levelDefinition, 'sort_order' => $index + 1],
                );
                $created['levels'] += (int) $level->wasRecentlyCreated;
            }
        }

        $created['sessions'] = $this->seedSessions();
        $created['subjects'] = $this->seedSubjects();

        $this->command?->info(sprintf(
            'Academic structure in place (%d programs, %d levels, %d sessions, %d subjects added).',
            $created['programs'],
            $created['levels'],
            $created['sessions'],
            $created['subjects'],
        ));
    }

    private function seedSessions(): int
    {
        $added = 0;

        foreach (range(2015, 2025) as $startYear) {
            $label = $startYear.'-'.substr((string) ($startYear + 1), -2);

            $session = AcademicSession::firstOrCreate(
                ['slug' => $label],
                [
                    'label' => $label,
                    'start_year' => $startYear,
                    'end_year' => $startYear + 1,
                    'sort_order' => $startYear - 2015,
                ],
            );

            $added += (int) $session->wasRecentlyCreated;
        }

        if (! AcademicSession::where('is_current', true)->exists()) {
            AcademicSession::where('slug', '2025-26')->update(['is_current' => true]);
        }

        return $added;
    }

    private function seedSubjects(): int
    {
        $added = 0;

        foreach (self::SUBJECTS as $programSlug => $byLevel) {
            $program = Program::where('slug', $programSlug)->first();

            if ($program === null) {
                continue;
            }

            foreach ($byLevel as $levelSlug => $subjects) {
                $levelId = $levelSlug !== ''
                    ? ProgramLevel::where('program_id', $program->id)->where('slug', $levelSlug)->value('id')
                    : null;

                foreach ($subjects as $index => $subject) {
                    $exists = Subject::where('program_id', $program->id)
                        ->where('program_level_id', $levelId)
                        ->where('name_en', $subject['name_en'])
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    Subject::create([
                        'program_id' => $program->id,
                        'program_level_id' => $levelId,
                        'slug' => Slug::for(Subject::class, $subject['name_en'], fallbackPrefix: 'subject', suffixes: [$program->slug]),
                        'name_bn' => $subject['name_bn'],
                        'name_en' => $subject['name_en'],
                        'sort_order' => $index + 1,
                    ]);

                    $added++;
                }
            }
        }

        return $added;
    }
}
