<?php

namespace App\Services\Notice;

use App\DTOs\FilterData;
use App\Enums\ContentStatus;
use App\Models\Notice;
use App\Support\Slug;
use App\Utilities\Asset;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\UploadedFile;

final class NoticeService
{
    /**
     * @return Paginator<int, Notice>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return Notice::query()
            ->with([
                'program:id,name_bn,name_en',
                'session:id,label',
                'subject:id,name_bn,name_en',
            ])
            ->searchable($filters->search, ['title_bn', 'title_en', 'body_bn', 'body_en'])
            ->filterable($filters->only(['category', 'status', 'program_id', 'academic_session_id']))
            ->orderByDesc('is_pinned')
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $counts = [];

        foreach (ContentStatus::cases() as $status) {
            $counts[$status->value] = Notice::where('status', $status)->count();
        }

        return $counts;
    }

    public function create(array $data, ?UploadedFile $attachment, ?int $userId = null): Notice
    {
        $data['slug'] = Slug::for(
            Notice::class,
            ($data['title_en'] ?? null) ?: $data['title_bn'],
            fallbackPrefix: 'notice',
        );
        $data['status'] = ContentStatus::Draft;
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;

        if ($attachment !== null) {
            $data = [...$data, ...$this->storeAttachment($attachment)];
        }

        try {
            return Notice::create($data);
        } catch (\Throwable $e) {
            Asset::removeFile($data['attachment_path'] ?? null, $data['attachment_disk'] ?? null);

            throw $e;
        }
    }

    public function update(
        Notice $notice,
        array $data,
        ?UploadedFile $attachment,
        bool $removeAttachment,
        ?int $userId = null,
    ): Notice {
        unset($data['status'], $data['published_at']);

        $data['updated_by'] = $userId;

        $oldPath = $notice->attachment_path;
        $oldDisk = $notice->attachment_disk;

        if ($attachment !== null) {
            $data = [...$data, ...$this->storeAttachment($attachment)];
        } elseif ($removeAttachment) {
            $data['attachment_disk'] = null;
            $data['attachment_path'] = null;
            $data['attachment_name'] = null;
            $data['attachment_size'] = null;
        }

        try {
            $notice->update($data);
        } catch (\Throwable $e) {
            if (($data['attachment_path'] ?? null) !== null && ($data['attachment_path'] ?? null) !== $oldPath) {
                Asset::removeFile($data['attachment_path'], $data['attachment_disk'] ?? null);
            }

            throw $e;
        }

        if ($oldPath !== null && $notice->attachment_path !== $oldPath) {
            Asset::removeFile($oldPath, $oldDisk);
        }

        return $notice;
    }

    public function publish(Notice $notice, ?int $userId = null): Notice
    {
        $notice->update([
            'status' => ContentStatus::Published,
            'published_at' => $notice->published_at ?? now(),
            'updated_by' => $userId,
        ]);

        return $notice;
    }

    public function unpublish(Notice $notice, ContentStatus $status, ?int $userId = null): Notice
    {
        $notice->update([
            'status' => $status,
            'updated_by' => $userId,
        ]);

        return $notice;
    }

    public function delete(Notice $notice): void
    {
        $notice->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function storeAttachment(UploadedFile $attachment): array
    {
        $disk = (string) config('llb.material_disk');

        $path = $attachment->storeAs(
            '',
            Asset::generateUploadPath($attachment->getClientOriginalName(), 'notices'),
            $disk,
        );

        return [
            'attachment_disk' => $disk,
            'attachment_path' => $path,
            'attachment_name' => $attachment->getClientOriginalName(),
            'attachment_size' => $attachment->getSize(),
        ];
    }
}
