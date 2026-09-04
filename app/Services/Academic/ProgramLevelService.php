<?php

namespace App\Services\Academic;

use App\Models\Program;
use App\Models\ProgramLevel;
use Illuminate\Support\Str;

final class ProgramLevelService
{
    public function create(array $data): ProgramLevel
    {
        $data['slug'] = $this->uniqueSlug((int) $data['program_id'], $data['name_en'] ?? $data['name_bn']);

        $level = ProgramLevel::create($data);

        Program::whereKey($level->program_id)->update(['has_levels' => true]);

        return $level;
    }

    public function update(ProgramLevel $level, array $data): ProgramLevel
    {
        $level->update($data);

        return $level;
    }

    public function delete(ProgramLevel $level): void
    {
        $level->delete();
    }

    private function uniqueSlug(int $programId, ?string $source): string
    {
        $base = Str::slug(Str::ascii(trim((string) $source)));

        if ($base === '') {
            $base = 'level-'.Str::lower(Str::random(8));
        }

        $candidate = $base;

        for ($i = 2; ProgramLevel::where('program_id', $programId)->where('slug', $candidate)->exists(); $i++) {
            $candidate = $base.'-'.$i;
        }

        return $candidate;
    }
}
