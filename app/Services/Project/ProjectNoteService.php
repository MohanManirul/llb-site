<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class ProjectNoteService
{
    /**
     * @return Collection<int, ProjectNote>
     */
    public function listForProject(Project $project, ?User $user): Collection
    {
        return $project->notes()
            ->where('user_id', $user?->id)
            ->latest()
            ->get();
    }

    public function create(Project $project, array $data, ?User $user): ProjectNote
    {
        return $project->notes()->create([
            'company_id' => $project->company_id,
            'user_id' => $user?->id,
            'note' => $data['note'],
        ]);
    }

    public function update(ProjectNote $note, array $data): ProjectNote
    {
        $note->update(['note' => $data['note']]);

        return $note->refresh();
    }

    public function delete(ProjectNote $note): void
    {
        $note->delete();
    }
}
