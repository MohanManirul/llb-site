<?php

namespace App\Services\Company;

use App\DTOs\FilterData;
use App\Models\Company;
use App\Utilities\Asset;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

final class CompanyService
{
    /**
     * @return Paginator<int, Company>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return Company::query()
            ->searchable($filters->search, ['name', 'email'])
            ->filterable($filters->only(['is_active']))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    public function create(array $data, ?string $logoPath = null): Company
    {
        $data['logo'] = $logoPath;

        try {
            return Company::create($data);
        } catch (\Throwable $e) {
            Asset::removeFile($logoPath);
            Asset::removeFile(getThumbnailPath($logoPath));

            throw $e;
        }
    }

    public function update(Company $company, array $data, ?string $logoPath = null): Company
    {
        $oldLogo = $company->logo;
        $data['logo'] = $logoPath;

        try {
            $company->update($data);

            return $company;
        } catch (\Throwable $e) {
            if ($logoPath !== $oldLogo) {
                Asset::removeFile($logoPath);
                Asset::removeFile(getThumbnailPath($logoPath));
            }

            throw $e;
        }
    }

    public function delete(Company $company): void
    {
        $company->delete();
    }

    /**
     * @return Collection<int, array{value: int, label: string, description: ?string, image_url: ?string, thumbnail_url: ?string}>
     */
    public function searchOptions(?string $search = null): Collection
    {
        $search = trim((string) $search);

        return Company::query()
            ->where('is_active', true)
            ->when($search !== '', fn ($query) => $query->whereLike('name', "%{$search}%"))
            ->latest()
            ->get()
            ->map(fn (Company $company) => [
                'value' => $company->id,
                'label' => $company->name,
                'description' => $company->email,
                'image_url' => $company->logo_url,
                'thumbnail_url' => $company->thumbnail_url,
            ]);
    }
}
