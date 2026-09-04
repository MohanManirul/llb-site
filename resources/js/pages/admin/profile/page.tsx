import { useEffect, useState, type ChangeEvent, type FormEvent, type ReactNode } from 'react';
import { router, usePage } from '@inertiajs/react';
import { CheckIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import { TextInput, Button, ImageUpload } from '@/components/ui';
import api from '@/lib/api-client';
import { flash, errorMessage, validationErrors } from '@/lib/flash';

interface ProfileUser {
    id: number;
    name: string;
    email: string;
    image_url?: string | null;
    thumbnail_url?: string | null;
    photo_editable?: boolean;
}

interface ProfileFormData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    image: File | null;
}

function initialsFrom(name?: string | null) {
    return (name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');
}

export default function Profile() {
    const { auth } = usePage().props;

    const [user, setUser] = useState<ProfileUser | null>(auth?.user ?? null);

    const [data, setDataState] = useState<ProfileFormData>({
        name: auth?.user?.name ?? '',
        email: auth?.user?.email ?? '',
        password: '',
        password_confirmation: '',
        image: null,
    });
    const [removeImage, setRemoveImage] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const [uploadProgress, setUploadProgress] = useState<number | null>(null);

    function setData<K extends keyof ProfileFormData>(field: K, value: ProfileFormData[K]) {
        setDataState((current) => ({ ...current, [field]: value }));
    }

    async function fetchProfile() {
        try {
            const { data: response } = await api.get('/profile');
            const profile = response.result;
            setUser(profile.user);
            setDataState((current) => ({
                ...current,
                name: profile.user.name,
                email: profile.user.email,
            }));
        } catch (error) {
            flash.error(errorMessage(error, 'Could not load your profile.'));
        }
    }

    useEffect(() => {
        fetchProfile();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    async function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        setProcessing(true);
        setUploadProgress(data.image ? 0 : null);
        setErrors({});

        const payload = new FormData();
        payload.append('name', data.name ?? '');
        payload.append('email', data.email ?? '');
        if (data.password) {
            payload.append('password', data.password);
            payload.append('password_confirmation', data.password_confirmation);
        }
        if (data.image) {
            payload.append('image', data.image);
        } else if (removeImage) {
            payload.append('remove_image', '1');
        }
        payload.append('_method', 'patch');

        try {
            const { data: response } = await api.post('/profile', payload, {
                onUploadProgress: (event) => {
                    if (data.image && event.total) {
                        setUploadProgress(Math.round((event.loaded / event.total) * 100));
                    }
                },
            });
            setUser(response.result);
            setDataState((current) => ({
                ...current,
                password: '',
                password_confirmation: '',
                image: null,
            }));
            setRemoveImage(false);
            flash.success(response.message ?? 'Profile updated successfully.');
            router.reload({ only: ['auth'] });
        } catch (error) {
            if ((error as { response?: { status?: number } })?.response?.status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(
                    errorMessage(error, 'Could not update your profile.')
                );
            }
        } finally {
            setProcessing(false);
            setUploadProgress(null);
        }
    }

    return (
        <>
            <PageHeader
                title="Profile" />

            <div className="w-full space-y-6">
                <div className="flex items-center gap-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    {user?.image_url ? (
                        <img
                            src={user.image_url}
                            alt={user?.name}
                            className="h-14 w-14 shrink-0 rounded-full object-cover"
                        />
                    ) : (
                        <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-accent text-lg font-semibold text-white">
                            {initialsFrom(user?.name) || 'U'}
                        </div>
                    )}
                    <div className="min-w-0">
                        <h2 className="truncate text-lg font-semibold text-gray-800">
                            {user?.name}
                        </h2>
                        <p className="truncate text-sm text-gray-500">{user?.email}</p>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="rounded-card border border-hairline bg-white shadow-sm">
                        <div className="border-b border-gray-100 px-5 py-4">
                            <h3 className="text-md font-semibold text-gray-800">
                                Profile information
                            </h3>
                            <p className="text-sm text-gray-500">
                                Update your name and email address.
                            </p>
                        </div>
                        <div className="space-y-5 p-5">
                            {user?.photo_editable && (
                                <div>
                                    <ImageUpload
                                        label="Profile picture"
                                        name="image"
                                        layout="inline"
                                        value={user?.image_url ?? null}
                                        onChange={(file) => {
                                            setData('image', file);
                                            setRemoveImage(false);
                                        }}
                                        error={errors.image}
                                        helperText={
                                            removeImage
                                                ? 'Photo will be removed after you save.'
                                                : 'JPG, PNG or WEBP. Max 2 MB.'
                                        }
                                    />
                                    {(user?.image_url || data.image) && !removeImage && (
                                        <Button
                                            variant="link"
                                            tone="danger"
                                            size="sm"
                                            className="mt-2"
                                            onClick={() => {
                                                setData('image', null);
                                                setRemoveImage(true);
                                            }}
                                        >
                                            Remove photo
                                        </Button>
                                    )}
                                    {removeImage && (
                                        <Button
                                            variant="link"
                                            tone="muted"
                                            size="sm"
                                            className="mt-2"
                                            onClick={() => setRemoveImage(false)}
                                        >
                                            Cancel
                                        </Button>
                                    )}
                                    {uploadProgress !== null && (
                                        <div className="mt-2 h-1.5 w-full max-w-xs overflow-hidden rounded-full bg-gray-100">
                                            <div
                                                className="h-full rounded-full bg-brand-accent transition-all"
                                                style={{ width: `${uploadProgress}%` }}
                                            />
                                        </div>
                                    )}
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
                        </div>
                    </div>

                    <div className="rounded-card border border-hairline bg-white shadow-sm">
                        <div className="border-b border-gray-100 px-5 py-4">
                            <h3 className="text-md font-semibold text-gray-800">
                                Security
                            </h3>
                            <p className="text-sm text-gray-500">
                                Leave the fields blank to keep your current password.
                            </p>
                        </div>
                        <div className="space-y-5 p-5">
                            <TextInput
                                label="New password"
                                type="password"
                                value={data.password}
                                onChange={(e: ChangeEvent<HTMLInputElement>) => setData('password', e.target.value)}
                                error={errors.password}
                                autoComplete="new-password"
                            />
                            <TextInput
                                label="Confirm password"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e: ChangeEvent<HTMLInputElement>) =>
                                    setData('password_confirmation', e.target.value)
                                }
                                autoComplete="new-password"
                            />
                        </div>
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            <CheckIcon className="h-4 w-4" />
                            {processing ? 'Saving…' : 'Save changes'}
                        </Button>
                    </div>
                </form>

            </div>

        </>
    );
}

Profile.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
