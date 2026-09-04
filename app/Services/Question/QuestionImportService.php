<?php

namespace App\Services\Question;

use App\Enums\ContentStatus;
use App\Enums\ExamStage;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class QuestionImportService
{
    private const int MAX_ROWS = 1000;

    private const array HEADER = [
        'subject', 'type', 'exam_stage', 'exam_year',
        'question_bn', 'question_en',
        'option_1', 'option_2', 'option_3', 'option_4', 'option_5',
        'correct_option', 'explanation_bn', 'explanation_en', 'reference',
    ];

    public function template(): string
    {
        $sample = [
            'bar-council', 'mcq', 'mcq', '2023',
            'দেওয়ানি কার্যবিধির কত ধারায় রেস জুডিকাটা বর্ণিত হয়েছে?', 'Which section of CPC deals with res judicata?',
            'ধারা ১০', 'ধারা ১১', 'ধারা ১২', 'ধারা ১৫১', '',
            '2', 'ধারা ১১-তে রেস জুডিকাটা বর্ণিত হয়েছে।', 'Section 11 of CPC embodies res judicata.', 'CPC, 1908',
        ];

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, self::HEADER);
        fputcsv($handle, $sample);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return "\u{FEFF}".$csv;
    }

    /**
     * @return array{imported: int, failed: int, errors: array<int, array{row: int, messages: array<int, string>}>}
     */
    public function import(UploadedFile $file, ?int $userId = null): array
    {
        [$rows, $errors] = $this->parse($file);

        if ($errors !== []) {
            return ['imported' => 0, 'failed' => count($errors), 'errors' => $errors];
        }

        DB::transaction(function () use ($rows, $userId) {
            foreach ($rows as $row) {
                $question = Question::create([
                    'type' => $row['type'],
                    'subject_id' => $row['subject_id'],
                    'exam_stage' => $row['exam_stage'],
                    'exam_year' => $row['exam_year'],
                    'question_bn' => $row['question_bn'],
                    'question_en' => $row['question_en'],
                    'explanation_bn' => $row['explanation_bn'],
                    'explanation_en' => $row['explanation_en'],
                    'reference' => $row['reference'],
                    'status' => ContentStatus::Draft,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                foreach ($row['options'] as $index => $option) {
                    $question->options()->create([
                        'option_bn' => $option,
                        'is_correct' => $index + 1 === $row['correct_option'],
                        'sort_order' => $index + 1,
                    ]);
                }
            }
        });

        return ['imported' => count($rows), 'failed' => 0, 'errors' => []];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array{row: int, messages: array<int, string>}>}
     */
    private function parse(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        $header = fgetcsv($handle);

        if ($header !== false && isset($header[0])) {
            $header[0] = preg_replace('/^\x{FEFF}/u', '', $header[0]);
        }

        if ($header === false || array_map('trim', $header) !== self::HEADER) {
            fclose($handle);

            throw ValidationException::withMessages([
                'file' => 'The CSV header does not match the template. Download the template and try again.',
            ]);
        }

        $subjectsBySlug = Subject::pluck('id', 'slug');
        $subjectIds = $subjectsBySlug->values()->flip();

        $rows = [];
        $errors = [];
        $line = 1;

        while (($raw = fgetcsv($handle)) !== false) {
            $line++;

            if (count(array_filter($raw, fn ($cell) => trim((string) $cell) !== '')) === 0) {
                continue;
            }

            if (count($rows) + count($errors) >= self::MAX_ROWS) {
                $errors[] = ['row' => $line, 'messages' => ['The file exceeds the limit of '.self::MAX_ROWS.' rows.']];
                break;
            }

            $data = array_combine(self::HEADER, array_pad(array_map('trim', $raw), count(self::HEADER), ''));
            $data = array_map(fn ($value) => $value === '' ? null : $value, $data);

            $subjectId = null;

            if ($data['subject'] !== null) {
                $subjectId = $subjectsBySlug[$data['subject']]
                    ?? (is_numeric($data['subject']) && $subjectIds->has((int) $data['subject']) ? (int) $data['subject'] : null);
            }

            $options = array_values(array_filter([
                $data['option_1'], $data['option_2'], $data['option_3'], $data['option_4'], $data['option_5'],
            ], fn ($option) => $option !== null));

            $isMcq = $data['type'] === QuestionType::Mcq->value;

            $validator = Validator::make([
                'subject_id' => $subjectId,
                'type' => $data['type'],
                'exam_stage' => $data['exam_stage'],
                'exam_year' => $data['exam_year'],
                'question_bn' => $data['question_bn'],
                'correct_option' => $data['correct_option'],
                'options' => $options,
            ], [
                'subject_id' => ['required', 'integer'],
                'type' => ['required', Rule::enum(QuestionType::class)],
                'exam_stage' => ['nullable', Rule::enum(ExamStage::class)],
                'exam_year' => ['nullable', 'integer', 'between:1972,2100'],
                'question_bn' => ['required', 'string'],
                'correct_option' => $isMcq
                    ? ['required', 'integer', 'between:1,'.max(1, count($options))]
                    : ['prohibited'],
                'options' => $isMcq
                    ? ['required', 'array', 'min:2', 'max:5']
                    : ['array', 'max:0'],
            ], [], [
                'subject_id' => 'subject',
            ]);

            if ($validator->fails()) {
                $errors[] = ['row' => $line, 'messages' => $validator->errors()->all()];

                continue;
            }

            $rows[] = [
                'type' => $data['type'],
                'subject_id' => $subjectId,
                'exam_stage' => $data['exam_stage'],
                'exam_year' => $data['exam_year'] !== null ? (int) $data['exam_year'] : null,
                'question_bn' => $data['question_bn'],
                'question_en' => $data['question_en'],
                'explanation_bn' => $data['explanation_bn'],
                'explanation_en' => $data['explanation_en'],
                'reference' => $data['reference'],
                'correct_option' => $data['correct_option'] !== null ? (int) $data['correct_option'] : null,
                'options' => $data['type'] === QuestionType::Mcq->value ? $options : [],
            ];
        }

        fclose($handle);

        return [$rows, $errors];
    }
}
