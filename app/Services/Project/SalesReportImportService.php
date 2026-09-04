<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Rules\SalesReport\NoOverlappingWeek;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use SplFileObject;

final class SalesReportImportService
{
    public const REQUIRED_COLUMNS = [
        'week_start',
        'total_sales',
        'total_order_quantity',
        'total_amount_spent',
    ];

    private const WEEK_LENGTH = 7;

    private const MAX_SKIPPED_DETAILS = 200;

    private const DATE_FORMATS = ['Y-m-d', 'Y/m/d', 'd-m-Y', 'd/m/Y', 'd.m.Y'];

    private const HEADER_MAP = [
        'week_start' => 'week_start',
        'week' => 'week_start',
        'start' => 'week_start',
        'start_date' => 'week_start',
        'from' => 'week_start',
        'week_end' => 'week_end',
        'end' => 'week_end',
        'end_date' => 'week_end',
        'to' => 'week_end',
        'total_sales' => 'total_sales',
        'sales' => 'total_sales',
        'sale' => 'total_sales',
        'total_sale' => 'total_sales',
        'total_order_quantity' => 'total_order_quantity',
        'total_order' => 'total_order_quantity',
        'order_quantity' => 'total_order_quantity',
        'orders' => 'total_order_quantity',
        'quantity' => 'total_order_quantity',
        'total_amount_spent' => 'total_amount_spent',
        'amount_spent' => 'total_amount_spent',
        'total_spent' => 'total_amount_spent',
        'spent' => 'total_amount_spent',
        'description' => 'description',
        'note' => 'description',
        'notes' => 'description',
        'remarks' => 'description',
    ];

    public function __construct(
        private readonly SalesReportService $salesReportService,
    ) {}

    public function headerProblem(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_readable($path)) {
            return 'The CSV file could not be read.';
        }

        $columns = null;
        $dataRows = 0;

        foreach ($this->rows($path) as $row) {
            if ($columns === null) {
                $columns = $this->mapHeader($row);

                continue;
            }

            $dataRows++;

            break;
        }

        if ($columns === null) {
            return 'The CSV file is empty.';
        }

        $missing = array_values(
            array_diff(self::REQUIRED_COLUMNS, array_keys($columns))
        );

        if ($missing !== []) {
            return 'CSV is missing required columns: '.implode(', ', $missing).'.';
        }

        if ($dataRows === 0) {
            return 'The CSV file has no data rows.';
        }

        return null;
    }

    /**
     * @return array{columns: array<string, int>, rows: array<int, array{line: int, values: array<int, string|null>}>}
     */
    public function readRows(UploadedFile $file): array
    {
        $columns = null;
        $rows = [];
        $rowNumber = 0;

        foreach ($this->rows((string) $file->getRealPath()) as $row) {
            $rowNumber++;

            if ($columns === null) {
                $columns = $this->mapHeader($row);

                continue;
            }

            $rows[] = ['line' => $rowNumber, 'values' => $row];
        }

        return ['columns' => $columns ?? [], 'rows' => $rows];
    }

    /**
     * @param  array<int, array{line: int, values: array<int, string|null>}>  $buffer
     * @param  array<string, int>  $columns
     * @return array{imported: int, skipped: int, details: array<int, array<string, mixed>>}
     */
    public function importRows(Project $project, array $buffer, array $columns): array
    {
        $imported = 0;
        $skipped = 0;
        $details = [];

        foreach ($buffer as $entry) {
            $outcome = $this->importRow($project, $entry['line'], $entry['values'], $columns);

            if ($outcome === null) {
                $imported++;

                continue;
            }

            $skipped++;
            $details = $this->collectDetails($details, [$outcome]);
        }

        Log::info('Sales report CSV import finished.', [
            'project_id' => $project->id,
            'rows' => count($buffer),
            'imported_rows' => $imported,
            'skipped_rows' => $skipped,
        ]);

        return ['imported' => $imported, 'skipped' => $skipped, 'details' => $details];
    }

    /**
     * @param  array<int, string|null>  $values
     * @param  array<string, int>  $columns
     * @return array<string, mixed>|null
     */
    private function importRow(Project $project, int $line, array $values, array $columns): ?array
    {
        $weekStartRaw = $this->value($values, $columns, 'week_start');
        $weekStart = $this->date($weekStartRaw);

        if ($weekStart === null) {
            return $this->detail($line, $weekStartRaw, 'Week start must be a valid date (YYYY-MM-DD).');
        }

        $weekEnd = $weekStart->addDays(self::WEEK_LENGTH - 1);
        $weekEndRaw = $this->value($values, $columns, 'week_end');

        if ($weekEndRaw !== '') {
            $given = $this->date($weekEndRaw);

            if ($given === null || ! $given->isSameDay($weekEnd)) {
                return $this->detail($line, $weekStartRaw, sprintf(
                    'A weekly report covers 7 days, so week end must be %s.',
                    $weekEnd->toDateString(),
                ));
            }
        }

        $data = [
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'total_sales' => $this->number($this->value($values, $columns, 'total_sales')),
            'total_order_quantity' => $this->number($this->value($values, $columns, 'total_order_quantity')),
            'total_amount_spent' => $this->number($this->value($values, $columns, 'total_amount_spent')),
            'description' => $this->value($values, $columns, 'description'),
        ];

        $validator = Validator::make($data, [
            'week_start' => [
                'required',
                'date',
                new NoOverlappingWeek($project->id, $data['week_end']),
            ],
            'week_end' => ['required', 'date', 'after_or_equal:week_start'],
            'total_sales' => ['required', 'numeric', 'min:0'],
            'total_order_quantity' => ['required', 'integer', 'min:0'],
            'total_amount_spent' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->detail($line, $weekStart->toDateString(), (string) $validator->errors()->first());
        }

        try {
            $this->salesReportService->createForProject($project, [
                ...$validator->validated(),
                'description' => $data['description'] === '' ? null : $data['description'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Sales report CSV import row failed to save.', [
                'project_id' => $project->id,
                'row' => $line,
                'week_start' => $data['week_start'],
                'exception' => $e->getMessage(),
            ]);

            return $this->detail($line, $data['week_start'], 'The report could not be saved.');
        }

        return null;
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    private function rows(string $path): iterable
    {
        $handle = new SplFileObject($path);
        $handle->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        foreach ($handle as $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            yield $row;
        }
    }

    /**
     * @param  array<int, string|null>  $header
     * @return array<string, int>
     */
    private function mapHeader(array $header): array
    {
        $columns = [];

        foreach ($header as $index => $label) {
            $key = str_replace([' ', '-'], '_', strtolower(trim((string) preg_replace('/^\x{FEFF}/u', '', (string) $label))));

            if (isset(self::HEADER_MAP[$key])) {
                $columns[self::HEADER_MAP[$key]] = $index;
            }
        }

        return $columns;
    }

    private function date(string $value): ?CarbonImmutable
    {
        if ($value === '') {
            return null;
        }

        foreach (self::DATE_FORMATS as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);

            if ($date !== false && $date->format($format) === $value) {
                return CarbonImmutable::instance($date);
            }
        }

        return null;
    }

    private function number(string $value): string
    {
        return (string) preg_replace('/[^0-9.\-]/', '', $value);
    }

    /**
     * @param  array<int, string|null>  $values
     * @param  array<string, int>  $columns
     */
    private function value(array $values, array $columns, string $key): string
    {
        $index = $columns[$key] ?? null;

        if ($index === null) {
            return '';
        }

        return trim((string) ($values[$index] ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(int $line, string $weekStart, string $reason): array
    {
        return [
            'row' => $line,
            'week_start' => $weekStart,
            'reason' => $reason,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $details
     * @param  array<int, array<string, mixed>>  $new
     * @return array<int, array<string, mixed>>
     */
    private function collectDetails(array $details, array $new): array
    {
        if (count($details) >= self::MAX_SKIPPED_DETAILS) {
            return $details;
        }

        return array_slice([...$details, ...$new], 0, self::MAX_SKIPPED_DETAILS);
    }
}
