<?php

namespace App\Services\User;

use App\DTOs\FilterData;
use App\Models\User;
use App\Utilities\Asset;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final class UserService
{
    /**
     * @return Paginator<int, User>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return User::query()
            ->with(['roles:id,name', 'permissions:id,name'])
            ->searchable($filters->search, ['name', 'email', 'phone'])
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchOptions(?string $search = null, ?int $keepUserId = null, ?int $companyId = null): Collection
    {
        $search = trim((string) $search);

        return User::query()
            ->where(fn ($query) => $query
                ->whereDoesntHave('employees', fn ($employee) => $employee
                    ->withTrashed()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId)))
                ->when($keepUserId, fn ($q) => $q->orWhere('id', $keepUserId)))
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($q) => $q
                    ->whereLike('name', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%")))
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone', 'image'])
            ->map(fn (User $user) => [
                'value' => $user->id,
                'label' => $user->name,
                'description' => $user->email,
                'phone' => $user->phone,
                'image_url' => $user->image_url,
                'thumbnail_url' => $user->thumbnail_url,
            ]);
    }

    /**
     * @return Collection<int, string>
     */
    public function assignableRoles(): Collection
    {
        return Role::where('guard_name', 'web')->orderBy('name')->pluck('name');
    }

    public function create(array $data, ?string $imagePath = null): User
    {
        try {
            return DB::transaction(function () use ($data, $imagePath) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'image' => $imagePath,
                    'password' => $data['password'],
                ]);

                $this->syncRole($user, $data);

                return $user;
            });
        } catch (\Throwable $e) {
            Asset::removeFile($imagePath);
            Asset::removeFile(getThumbnailPath($imagePath));

            throw $e;
        }
    }

    public function update(User $user, array $data, ?string $imagePath = null): User
    {
        $oldImage = $user->image;

        try {
            return DB::transaction(function () use ($user, $data, $imagePath) {
                $user->name = $data['name'];
                $user->email = $data['email'];
                $user->phone = $data['phone'] ?? null;
                $user->image = $imagePath;

                if (! empty($data['password'])) {
                    $user->password = $data['password'];
                }

                $user->save();
                $this->syncRole($user, $data);

                return $user;
            });
        } catch (\Throwable $e) {
            if ($imagePath !== $oldImage) {
                Asset::removeFile($imagePath);
                Asset::removeFile(getThumbnailPath($imagePath));
            }

            throw $e;
        }
    }

    public function delete(User $user): void
    {
        $image = $user->image;

        $user->delete();

        Asset::removeFile($image);
        Asset::removeFile(getThumbnailPath($image));
    }

    private function syncRole(User $user, array $data): void
    {
        $role = $data['role'] ?? null;

        $user->syncRoles($role !== null && $role !== '' ? [$role] : []);
    }
}
