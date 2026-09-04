import { ChangeEvent, FormEvent, useEffect, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { CheckIcon } from '@heroicons/react/24/outline';
import { TextInput, Checkbox, Button } from '@/components/ui';
import api from '@/lib/api-client';
import { flash, errorMessage, validationErrors } from '@/lib/flash';
import { PermissionGroup, Role } from '../types';

interface RoleFormProps {
    role?: Role;
}

interface RoleFormData {
    name: string;
    permissions: string[];
}

export default function RoleForm({ role }: RoleFormProps) {
    const isEdit = Boolean(role);

    const [data, setDataState] = useState<RoleFormData>({
        name: role?.name ?? '',
        permissions: role?.permissions ?? [],
    });
    const [groups, setGroups] = useState<PermissionGroup[]>([]);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    function setData<K extends keyof RoleFormData>(field: K, value: RoleFormData[K]) {
        setDataState((current) => ({ ...current, [field]: value }));
    }

    useEffect(() => {
        let cancelled = false;

        api.get('/admin/roles/permission-groups')
            .then(({ data: response }) => {
                if (!cancelled) setGroups(response.result ?? []);
            })
            .catch((error) => {
                flash.error(
                    errorMessage(error, 'Could not load the permissions.')
                );
            });

        return () => {
            cancelled = true;
        };
    }, []);

    async function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        try {
            if (isEdit && role) {
                await api.put(`/admin/roles/${role.id}`, data);
            } else {
                await api.post('/admin/roles', data);
            }

            flash.success(
                isEdit
                    ? 'Role updated successfully.'
                    : 'Role created successfully.'
            );
            router.visit('/admin/roles');
        } catch (error) {
            if ((error as { response?: { status?: number } })?.response?.status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not save the role.'));
            }
            setProcessing(false);
        }
    }

    const checked = new Set(data.permissions);

    function togglePermission(name: string) {
        setData(
            'permissions',
            checked.has(name)
                ? data.permissions.filter((p) => p !== name)
                : [...data.permissions, name]
        );
    }

    function toggleModule(group: PermissionGroup) {
        const allChecked = group.permissions.every((p) => checked.has(p));
        const withoutModule = data.permissions.filter(
            (p) => !group.permissions.includes(p)
        );
        setData(
            'permissions',
            allChecked ? withoutModule : [...withoutModule, ...group.permissions]
        );
    }

    const allPermissions = groups.flatMap((g) => g.permissions);
    const allChecked = allPermissions.every((p) => checked.has(p));

    function toggleAll() {
        setData('permissions', allChecked ? [] : allPermissions);
    }

    function moduleTitle(module: string) {
        const words = module.replaceAll('-', ' ');
        return words.charAt(0).toUpperCase() + words.slice(1);
    }

    function permissionLabel(permission: string) {
        return permission.includes('.')
            ? permission.split('.').slice(1).join(' ')
            : permission;
    }

    return (
        <form onSubmit={submit} className="w-full space-y-6">
            <div className="rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-gray-100 px-5 py-4">
                    <h3 className="text-md font-semibold text-gray-800">
                        {isEdit ? 'Edit role' : 'Create role'}
                    </h3>
                </div>
                <div className="grid grid-cols-1 gap-2 p-5 sm:grid-cols-[11rem_1fr] sm:items-center sm:gap-6">
                    <label
                        htmlFor="name"
                        className="text-sm font-medium text-gray-700"
                    >
                        Name<span className="text-red-600"> *</span>
                    </label>
                    <TextInput
                        name="name"
                        value={data.name}
                        onChange={(e: ChangeEvent<HTMLInputElement>) => setData('name', e.target.value)}
                        error={errors.name}
                        required
                    />
                </div>
            </div>

            <div className="rounded-card border border-hairline bg-white shadow-sm">
                <div className="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 className="text-md font-semibold text-gray-800">
                            Permissions
                        </h3>
                        <p className="text-sm text-gray-500">
                            Everything a user with this role is allowed to do.
                        </p>
                    </div>
                    <Checkbox
                        label="Select all"
                        checked={allChecked}
                        onChange={toggleAll}
                    />
                </div>

                <div className="grid grid-cols-1 items-start gap-4 p-5 md:grid-cols-2">
                    {groups.map((group) => {
                        const moduleChecked = group.permissions.every((p) =>
                            checked.has(p)
                        );
                        return (
                            <div
                                key={group.module}
                                className="rounded-lg border border-gray-200 p-4"
                            >
                                <div className="mb-3 flex items-center justify-between gap-3">
                                    <h4 className="font-semibold text-gray-800">
                                        {moduleTitle(group.module)}
                                    </h4>
                                    <Checkbox
                                        label="Select all"
                                        checked={moduleChecked}
                                        onChange={() => toggleModule(group)}
                                    />
                                </div>
                                <div className="flex flex-col gap-2.5">
                                    {group.permissions.map((permission) => (
                                        <Checkbox
                                            key={permission}
                                            label={permissionLabel(permission)}
                                            checked={checked.has(permission)}
                                            onChange={() =>
                                                togglePermission(permission)
                                            }
                                        />
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>

                {errors.permissions && (
                    <p className="px-5 pb-4 text-xs text-red-600">
                        {errors.permissions}
                    </p>
                )}
            </div>

            <div className="flex justify-end gap-3">
                <Link href="/admin/roles">
                    <Button variant="secondary" type="button">
                        Cancel
                    </Button>
                </Link>
                <Button type="submit" disabled={processing}>
                    <CheckIcon className="h-4 w-4" />
                    {isEdit ? 'Save changes' : 'Create role'}
                </Button>
            </div>
        </form>
    );
}
