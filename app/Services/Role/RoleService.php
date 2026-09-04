<?php

namespace App\Services\Role;

use App\DTOs\FilterData;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RoleService
{
    private const GROUP_STRIP = ['view ', 'create ', 'edit ', 'delete ', 'manage '];

    private const GROUP_ALIASES = [
        'impersonate users' => 'users',
    ];

    /**
     * @return Paginator<int, Role>
     */
    public function paginate(FilterData $filters): Paginator
    {
        $usersCount = DB::table(config('permission.table_names.model_has_roles'))
            ->selectRaw('count(*)')
            ->whereColumn(
                config('permission.column_names.role_pivot_key') ?? 'role_id',
                'roles.id',
            );

        return Role::query()
            ->with('permissions:id,name')
            ->withCount('permissions')
            ->addSelect(['users_count' => $usersCount])
            ->when(
                $filters->search !== null,
                fn ($query) => $query->whereLike('name', "%{$filters->search}%"),
            )
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
            $role->syncPermissions($data['permissions'] ?? []);

            return $role->load('permissions');
        });
    }

    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data): Role {
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($data['permissions'] ?? []);

            return $role->load('permissions');
        });
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    /**
     * @return array<int, array{module: string, permissions: array<int, string>}>
     */
    public function permissionGroups(): array
    {
        return Permission::orderBy('name')
            ->pluck('name')
            ->groupBy(fn (string $name) => $this->groupNameFor($name))
            ->map(fn ($permissions, $module) => [
                'module' => $module,
                'permissions' => $permissions->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function groupNameFor(string $permission): string
    {
        $group = Str::lower(str_replace(self::GROUP_STRIP, '', $permission));

        return self::GROUP_ALIASES[$group] ?? $group;
    }
}
