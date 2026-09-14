<?php

namespace App\Services\TeacherApi;

use App\DTOs\FilterData;
use App\Enums\ContentStatus;
use App\Models\Teacher;
use App\Utilities\Asset;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

/**
 * Shared CRUD for the college-scoped content a teacher owns. Every query and
 * every write is pinned to the college of the teacher: college_id and
 * teacher_id are never taken from request input.
 */
abstract class CollegeContentService
{
    /**
     * @return class-string<Model>
     */
    abstract protected function model(): string;

    abstract protected function uploadFolder(): string;

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['title_bn', 'title_en', 'description_bn', 'description_en'];
    }

    /**
     * @return array<int, string>
     */
    protected function filterable(): array
    {
        return ['status'];
    }

    /**
     * @return array<int, string>
     */
    protected function relations(): array
    {
        return ['session:id,label'];
    }

    /**
     * @return Paginator<int, Model>
     */
    public function paginate(Teacher $teacher, FilterData $filters): Paginator
    {
        $model = $this->model();

        return $model::query()
            ->where('college_id', $teacher->college_id)
            ->with($this->relations())
            ->searchable($filters->search, $this->searchable())
            ->filterable($filters->only($this->filterable()))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(Teacher $teacher): array
    {
        $model = $this->model();
        $counts = [];

        foreach (ContentStatus::cases() as $status) {
            $counts[$status->value] = $model::query()
                ->where('college_id', $teacher->college_id)
                ->where('status', $status)
                ->count();
        }

        return $counts;
    }

    public function create(Teacher $teacher, array $data, ?UploadedFile $attachment): Model
    {
        $model = $this->model();

        $data['college_id'] = $teacher->college_id;
        $data['teacher_id'] = $teacher->id;
        $data['status'] = ContentStatus::Draft;

        if ($attachment !== null) {
            $data = [...$data, ...$this->storeAttachment($attachment)];
        }

        try {
            return $model::create($data);
        } catch (\Throwable $e) {
            Asset::removeFile($data['attachment_path'] ?? null, $data['attachment_disk'] ?? null);

            throw $e;
        }
    }

    public function update(Model $record, array $data, ?UploadedFile $attachment, bool $removeAttachment): Model
    {
        unset($data['status'], $data['published_at'], $data['college_id'], $data['teacher_id']);

        $oldPath = $record->attachment_path;
        $oldDisk = $record->attachment_disk;

        if ($attachment !== null) {
            $data = [...$data, ...$this->storeAttachment($attachment)];
        } elseif ($removeAttachment) {
            $data['attachment_disk'] = null;
            $data['attachment_path'] = null;
            $data['attachment_name'] = null;
            $data['attachment_size'] = null;
        }

        try {
            $record->update($data);
        } catch (\Throwable $e) {
            if (($data['attachment_path'] ?? null) !== null && ($data['attachment_path'] ?? null) !== $oldPath) {
                Asset::removeFile($data['attachment_path'], $data['attachment_disk'] ?? null);
            }

            throw $e;
        }

        if ($oldPath !== null && $record->attachment_path !== $oldPath) {
            Asset::removeFile($oldPath, $oldDisk);
        }

        return $record;
    }

    public function publish(Model $record): Model
    {
        $record->update([
            'status' => ContentStatus::Published,
            'published_at' => $record->published_at ?? now(),
        ]);

        return $record;
    }

    public function unpublish(Model $record, ContentStatus $status): Model
    {
        $record->update(['status' => $status]);

        return $record;
    }

    public function delete(Model $record): void
    {
        $record->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function storeAttachment(UploadedFile $attachment): array
    {
        $disk = (string) config('llb.material_disk');

        $path = $attachment->store('uploads/'.$this->uploadFolder(), $disk);

        return [
            'attachment_disk' => $disk,
            'attachment_path' => $path,
            'attachment_name' => $attachment->getClientOriginalName(),
            'attachment_size' => $attachment->getSize(),
        ];
    }
}
