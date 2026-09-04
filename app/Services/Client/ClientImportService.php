<?php

namespace App\Services\Client;

use App\Models\Client;
use App\Rules\PhoneNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use SplFileObject;

final class ClientImportService
{
    public const REQUIRED_COLUMNS = ['name', 'email', 'phone', 'password'];

    public const CHUNK_SIZE = 200;

    private const MAX_SKIPPED_DETAILS = 200;

    private const HEADER_MAP = [
        'name' => 'name',
        'client_name' => 'name',
        'full_name' => 'name',
        'email' => 'email',
        'email_address' => 'email',
        'phone' => 'phone',
        'phone_number' => 'phone',
        'mobile' => 'phone',
        'mobile_no' => 'phone',
        'mobile_number' => 'phone',
        'contact_number' => 'phone',
        'password' => 'password',
    ];

    public function headerProblem(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_readable($path)) {
            return 'The CSV file could not be read.';
        }

        $handle = new SplFileObject($path);
        $handle->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $header = $handle->current();

        if (! is_array($header) || $header === [null]) {
            return 'The CSV file is empty.';
        }

        $missing = array_values(
            array_diff(self::REQUIRED_COLUMNS, array_keys($this->mapHeader($header)))
        );

        if ($missing !== []) {
            return 'CSV is missing required columns: '.implode(', ', $missing).'.';
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
    public function importRows(array $buffer, array $columns): array
    {
        $seenEmails = [];
        $seenPhones = [];

        $result = $this->importChunk($buffer, $columns, $seenEmails, $seenPhones);

        Log::info('Client CSV import chunk finished.', [
            'rows' => count($buffer),
            'imported_rows' => $result['imported'],
            'skipped_rows' => $result['skipped'],
        ]);

        return $result;
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

    /**
     * @param  array<int, array{line: int, values: array<int, string|null>}>  $buffer
     * @param  array<string, int>  $columns
     * @param  array<string, true>  $seenEmails
     * @param  array<string, true>  $seenPhones
     * @return array{imported: int, skipped: int, details: array<int, array<string, mixed>>}
     */
    private function importChunk(array $buffer, array $columns, array &$seenEmails, array &$seenPhones): array
    {
        $candidates = [];
        $details = [];
        $skipped = 0;

        foreach ($buffer as $entry) {
            $data = [
                'name' => $this->value($entry['values'], $columns, 'name'),
                'email' => strtolower($this->value($entry['values'], $columns, 'email')),
                'phone' => formatPhoneNumber($this->value($entry['values'], $columns, 'phone')),
                'password' => $this->value($entry['values'], $columns, 'password'),
            ];

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:150'],
                'email' => ['required', 'email', 'max:50'],
                'phone' => ['bail', 'required', 'digits_between:8,15', new PhoneNumber],
                'password' => ['required', 'string', 'min:6', 'max:100'],
            ]);

            if ($validator->fails()) {
                $skipped++;
                $details[] = $this->detail($entry['line'], $data, (string) $validator->errors()->first());

                continue;
            }

            if (isset($seenEmails[$data['email']])) {
                $skipped++;
                $details[] = $this->detail($entry['line'], $data, 'Duplicate email inside the CSV file.');

                continue;
            }

            if (isset($seenPhones[$data['phone']])) {
                $skipped++;
                $details[] = $this->detail($entry['line'], $data, 'Duplicate phone inside the CSV file.');

                continue;
            }

            $seenEmails[$data['email']] = true;
            $seenPhones[$data['phone']] = true;

            $candidates[] = ['line' => $entry['line'], 'data' => $data];
        }

        if ($candidates === []) {
            return ['imported' => 0, 'skipped' => $skipped, 'details' => $details];
        }

        $emails = array_column(array_column($candidates, 'data'), 'email');
        $phones = array_column(array_column($candidates, 'data'), 'phone');

        $takenEmails = Client::withTrashed()->whereIn('email', $emails)->pluck('email')
            ->map(fn (string $email) => strtolower($email))
            ->all();

        $takenPhones = Client::withTrashed()->whereIn('phone', $phones)->pluck('phone')->all();

        $insertable = [];

        foreach ($candidates as $candidate) {
            if (in_array($candidate['data']['email'], $takenEmails, true)) {
                $skipped++;
                $details[] = $this->detail($candidate['line'], $candidate['data'], 'Email already exists.');

                continue;
            }

            if (in_array($candidate['data']['phone'], $takenPhones, true)) {
                $skipped++;
                $details[] = $this->detail($candidate['line'], $candidate['data'], 'Phone already exists.');

                continue;
            }

            $insertable[] = $candidate;
        }

        $imported = 0;

        foreach ($insertable as $candidate) {
            try {
                Client::create([
                    'name' => $candidate['data']['name'],
                    'email' => $candidate['data']['email'],
                    'phone' => $candidate['data']['phone'],
                    'password' => $candidate['data']['password'],
                    'is_active' => true,
                ]);

                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                $details[] = $this->detail($candidate['line'], $candidate['data'], 'Could not be saved: duplicate email or phone.');

                Log::warning('Client CSV import row failed to save.', [
                    'row' => $candidate['line'],
                    'email' => $candidate['data']['email'],
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'details' => $details];
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
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    private function detail(int $line, array $data, string $reason): array
    {
        return [
            'row' => $line,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
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
