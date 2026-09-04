<?php

namespace App\Services\Academic;

use App\DTOs\FilterData;
use App\Models\AcademicSession;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AcademicSessionService
{
    /**
     * @return Paginator<int, AcademicSession>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return AcademicSession::query()
            ->searchable($filters->search, ['label'])
            ->filterable($filters->only(['is_active']))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function options(): Collection
    {
        return AcademicSession::query()
            ->where('is_active', true)
            ->orderByDesc('start_year')
            ->get()
            ->map(fn (AcademicSession $session) => [
                'value' => $session->id,
                'label' => $session->label,
                'is_current' => $session->is_current,
            ]);
    }

    public function create(array $data): AcademicSession
    {
        $data['slug'] = Str::slug($data['label']);

        $session = AcademicSession::create($data);

        if ($session->is_current) {
            $this->markCurrent($session);
        }

        return $session;
    }

    public function update(AcademicSession $session, array $data): AcademicSession
    {
        $session->update($data);

        if ($session->is_current) {
            $this->markCurrent($session);
        }

        return $session;
    }

    public function markCurrent(AcademicSession $session): AcademicSession
    {
        DB::transaction(function () use ($session) {
            AcademicSession::whereKeyNot($session->id)->where('is_current', true)
                ->update(['is_current' => false]);

            AcademicSession::whereKey($session->id)->update(['is_current' => true]);
        });

        return $session->refresh();
    }

    public function delete(AcademicSession $session): void
    {
        $session->delete();
    }
}
