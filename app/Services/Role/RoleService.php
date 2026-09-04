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

        'project notes' => 'project notes',

        'project client info' => 'projects',
        'project client' => 'projects',
        'project contact' => 'projects',

        'pick call center orders' => 'call center',
        'unpick call center orders' => 'call center',
        'update call center order status' => 'call center',
        'all call center orders' => 'call center',
        'call center agents' => 'call center',
        'call center orders' => 'call center',
        'call center performance' => 'call center',
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
        $groups = Permission::orderBy('name')
            ->pluck('name')
            ->groupBy(fn (string $name) => $this->groupNameFor($name))
            ->map(fn ($permissions, $module) => [
                'module' => $module,
                'permissions' => $permissions->values()->all(),
            ])
            ->values()
            ->all();

        return $this->mergeProjectClientPermissions($groups);
    }

    private function groupNameFor(string $permission): string
    {
        $group = Str::lower(str_replace(self::GROUP_STRIP, '', $permission));

        return self::GROUP_ALIASES[$group] ?? $group;
    }

    /**
     * @param  array<int, array{module: string, permissions: array<int, string>}>  $groups
     * @return array<int, array{module: string, permissions: array<int, string>}>
     */
    private function mergeProjectClientPermissions(array $groups): array
    {
        $projectsGroup = collect($groups)->firstWhere('module', 'projects');

        if (! $projectsGroup) {
            return $groups;
        }

        $projectPerms = collect($projectsGroup['permissions']);

        if (! $projectPerms->contains('view project client') || ! $projectPerms->contains('view project contact')) {
            return $groups;
        }

        $filteredPerms = $projectPerms
            ->reject(fn ($p) => $p === 'view project client' || $p === 'view project contact')
            ->push('view project client info')
            ->unique()
            ->values()
            ->all();

        return collect($groups)
            ->map(fn ($group) => $group['module'] === 'projects'
                ? ['module' => 'projects', 'permissions' => $filteredPerms]
                : $group
            )
            ->values()
            ->all();
    }
}
