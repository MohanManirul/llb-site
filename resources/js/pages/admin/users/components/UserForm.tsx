import { ChangeEvent, FormEvent, useEffect, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { CheckIcon } from '@heroicons/react/24/outline';
import { TextInput, SelectInput, Button, ImageUpload } from '@/components/ui';
import api from '@/lib/api-client';
import { flash, errorMessage, validationErrors } from '@/lib/flash';
import { User } from '../types';

interface UserFormProps {
    user?: User;
}

interface UserFormData {
    name: string;
    email: string;
    phone: string;
    image: File | null;
    password: string;
    password_confirmation: string;
    role: string;
}

export default function UserForm({ user }: UserFormProps) {
    const isEdit = Boolean(user);

    const [data, setDataState] = useState<UserFormData>({
        name: user?.name ?? '',
        email: user?.email ?? '',
        phone: user?.phone ?? '',
        image: null,
        password: '',
        password_confirmation: '',
        role: user?.roles?.[0] ?? '',
    });
    const [removeImage, setRemoveImage] = useState(false);
    const [roles, setRoles] = useState<string[]>([]);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    function setData<K extends keyof UserFormData>(field: K, value: UserFormData[K]) {
        setDataState((current) => ({ ...current, [field]: value }));
    }

    useEffect(() => {
        let cancelled = false;

        api.get('/admin/users/roles')
            .then(({ data: response }) => {
                if (!cancelled) setRoles(response.result ?? []);
            })
            .catch((error) => {
                flash.error(errorMessage(error, 'Could not load the roles.'));
            });

        return () => {
            cancelled = true;
        };
    }, []);

    async function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const payload = new FormData();
        payload.append('name', data.name ?? '');
        payload.append('email', data.email ?? '');
        payload.append('phone', data.phone ?? '');
        if (data.password) {
            payload.append('password', data.password);
            payload.append('password_confirmation', data.password_confirmation);
        }
        payload.append('role', data.role ?? '');
        if (data.image) {
            payload.append('image', data.image);
        } else if (removeImage) {
            payload.append('remove_image', '1');
        }

        try {
            if (isEdit && user) {
                payload.append('_method', 'put');
                await api.post(`/admin/users/${user.id}`, payload);
            } else {
                await api.post('/admin/users', payload);
            }

            flash.success(
                isEdit
                    ? 'User updated successfully.'
                    : 'User created successfully.'
            );
            router.visit('/admin/users');
        } catch (error) {
            if ((error as { response?: { status?: number } })?.response?.status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not save the user.'));
            }
            setProcessing(false);
        }
    }

    return (
        <form onSubmit={submit} className="w-full space-y-6">
            <div className="rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-gray-100 px-5 py-4">
                    <h3 className="text-md font-semibold text-gray-800">Details</h3>
                    <p className="text-sm text-gray-500">
                        Basic information for this user.
                    </p>
                </div>
                <div className="space-y-5 p-5">
                    <ImageUpload
                        name="image"
                        label="Profile Photo"
                        value={removeImage ? null : (user?.image_url ?? null)}
                        onChange={(file: File) => {
                            setData('image', file);
                            setRemoveImage(false);
                        }}
                        error={errors.image}
                    />

                    {(user?.image_url || data.image) && !removeImage && (
                        <div className="text-center">
                            <Button
                                variant="link"
                                tone="danger"
                                size="sm"
                                onClick={() => {
                                    setData('image', null);
                                    setRemoveImage(true);
                                }}
                            >
                                Remove photo
                            </Button>
                        </div>
                    )}

                    <TextInput
                        label="Name"
                        value={data.name}
                        onChange={(e: ChangeEvent<HTMLInputElement>) => setData('name', e.target.value)}
                        error={errors.name}
                        required
                    />
                    <TextInput
                        label="Email"
                        type="email"
                        value={data.email}
                        onChange={(e: ChangeEvent<HTMLInputElement>) => setData('email', e.target.value)}
                        error={errors.email}
                        required
                    />
                    <TextInput
                        label="Phone"
                        value={data.phone}
                        onChange={(e: ChangeEvent<HTMLInputElement>) => setData('phone', e.target.value)}
                        error={errors.phone}
                    />
                </div>
            </div>

            {roles.length > 0 && (
                <div className="rounded-card border border-hairline bg-white shadow-sm">
                    <div className="border-b border-gray-100 px-5 py-4">
                        <h3 className="text-md font-semibold text-gray-800">Role</h3>
                        <p className="text-sm text-gray-500">
                            What this user is allowed to do. Optional — leave it
                            blank for no role. Manage the roles themselves under{' '}
                            <Link
                                href="/admin/roles"
                                className="text-indigo-600 hover:text-indigo-800"
                            >
                                Roles
                            </Link>
                            .
                        </p>
                    </div>
                    <div className="p-5">
                        <SelectInput
                            label="Role"
                            value={data.role}
                            onChange={(e: ChangeEvent<HTMLSelectElement>) =>
                                setData('role', e.target.value)
                            }
                            error={errors.role}
                        >
                            <option value="">No role</option>
                            {roles.map((role) => (
                                <option key={role} value={role}>
                                    {role}
                                </option>
                            ))}
                        </SelectInput>
                    </div>
                </div>
            )}

            <div className="rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-gray-100 px-5 py-4">
                    <h3 className="text-md font-semibold text-gray-800">Security</h3>
                    <p className="text-sm text-gray-500">
                        {isEdit
                            ? 'Leave the fields blank to keep the current password.'
                            : 'Set an initial password for this user.'}
                    </p>
                </div>
                <div className="space-y-5 p-5">
                    <TextInput
                        label="Password"
                        type="password"
                        value={data.password}
                        onChange={(e: ChangeEvent<HTMLInputElement>) => setData('password', e.target.value)}
                        error={errors.password}
                        autoComplete="new-password"
                        required={!isEdit}
                    />
                    <TextInput
                        label="Confirm Password"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e: ChangeEvent<HTMLInputElement>) =>
                            setData('password_confirmation', e.target.value)
                        }
                        autoComplete="new-password"
                        required={!isEdit}
                    />
                </div>
            </div>

            <div className="flex justify-end gap-3">
                <Link href="/admin/users">
                    <Button variant="secondary" type="button">
                        Cancel
                    </Button>
                </Link>
                <Button type="submit" disabled={processing}>
                    <CheckIcon className="h-4 w-4" />
                    {isEdit ? 'Save changes' : 'Create user'}
                </Button>
            </div>
        </form>
    );
}
