import { useState, type ChangeEvent, type FormEvent } from 'react';
import { Link, router } from '@inertiajs/react';
import { CheckIcon } from '@heroicons/react/24/outline';
import { TextInput, SelectInput, Button, EmailInput, ImageUpload } from '@/components/ui';
import api from '@/lib/api-client';
import { flash, errorMessage, validationErrors } from '@/lib/flash';

export interface ClientFormClient {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    address?: string | null;
    description?: string | null;
    image_url?: string | null;
    thumbnail_url?: string | null;
    is_active: boolean;
}

interface ClientFormData {
    name: string;
    email: string;
    password: string;
    passwordConfirmation: string;
    phone: string;
    address: string;
    description: string;
    image: File | null;
    isActive: boolean;
}

interface ClientFormProps {
    client?: ClientFormClient;
}

export default function ClientForm({ client }: ClientFormProps) {
    const isEdit = Boolean(client);

    const [data, setDataState] = useState<ClientFormData>({
        name: client?.name ?? "",
        email: client?.email ?? "",
        password: "",
        passwordConfirmation: "",
        phone: client?.phone ?? "",
        address: client?.address ?? "",
        description: client?.description ?? "",
        image: null,
        isActive: client?.is_active ?? true,
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    function setData<K extends keyof ClientFormData>(field: K, value: ClientFormData[K]) {
        setDataState((current) => ({ ...current, [field]: value }));
    }

    async function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const payload = new FormData();
        payload.append("name", data.name);
        payload.append("email", data.email ?? "");
        payload.append("phone", data.phone ?? "");
        payload.append("address", data.address ?? "");
        payload.append("is_active", data.isActive ? "1" : "0");

        if (data.password) {
            payload.append("password", data.password);
            payload.append("password_confirmation", data.passwordConfirmation);
        }

        if (data.image) {
            payload.append("image", data.image);
        }

        if (isEdit) {
            payload.append("_method", "put");
        }

        try {
            await api.post(
                isEdit ? `/admin/clients/${client!.id}` : "/admin/clients",
                payload
            );

            flash.success(
                isEdit
                    ? "Client updated successfully."
                    : data.password
                      ? "Client created. They can sign in at /login with this email and password."
                      : "Client created."
            );
            router.visit("/admin/clients");
        } catch (error) {
            if ((error as { response?: { status?: number } })?.response?.status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, "Could not save the client."));
            }
            setProcessing(false);
        }
    }

    return (
        <form onSubmit={submit} className="w-full space-y-6">
            <div className="rounded-card border border-hairline bg-white shadow-sm">
                <div className="space-y-8 p-6">
                    <section>
                        <ImageUpload
                            name="image"
                            label="Client Photo"
                            value={client?.image_url ?? null}
                            error={errors.image}
                            onChange={(file: File) => setData("image", file)}
                        />

                        <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                            <TextInput
                                label="Name"
                                value={data.name}
                                onChange={(e: ChangeEvent<HTMLInputElement>) =>
                                    setData("name", e.target.value)
                                }
                                error={errors.name}
                                required
                            />

                            <EmailInput
                                label="Email"
                                name="email"
                                value={data.email}
                                onChange={(e: ChangeEvent<HTMLInputElement>) =>
                                    setData("email", e.target.value)
                                }
                                error={errors.email}
                                required
                            />

                            <TextInput
                                label="Login password"
                                type="password"
                                value={data.password}
                                onChange={(e: ChangeEvent<HTMLInputElement>) =>
                                    setData("password", e.target.value)
                                }
                                error={errors.password}
                                autoComplete="new-password"
                                required={!isEdit}
                            />

                            <TextInput
                                label="Confirm password"
                                type="password"
                                value={data.passwordConfirmation}
                                onChange={(e: ChangeEvent<HTMLInputElement>) =>
                                    setData("passwordConfirmation", e.target.value)
                                }
                                error={errors.password_confirmation}
                                autoComplete="new-password"
                                required={!isEdit}
                                hint={
                                    isEdit
                                        ? "Only needed when you set a new password above."
                                        : "Type the same password again."
                                }
                            />

                            <TextInput
                                label="Phone"
                                value={data.phone}
                                onChange={(e: ChangeEvent<HTMLInputElement>) =>
                                    setData("phone", e.target.value)
                                }
                                error={errors.phone}
                                required
                            />

                            <TextInput
                                label="Address"
                                value={data.address}
                                onChange={(e: ChangeEvent<HTMLInputElement>) =>
                                    setData("address", e.target.value)
                                }
                                error={errors.address}
                            />

                        </div>
                    </section>

                    <section>
                        <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                            <SelectInput
                                label="Status"
                                value={data.isActive ? "1" : "0"}
                                onChange={(e: ChangeEvent<HTMLSelectElement>) =>
                                    setData("isActive", e.target.value === "1")
                                }
                                error={errors.is_active}
                            >
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </SelectInput>
                        </div>
                    </section>
                </div>
            </div>

            <div className="flex justify-end gap-3">
                <Link href="/admin/clients">
                    <Button variant="secondary" type="button">
                        Cancel
                    </Button>
                </Link>
                <Button type="submit" disabled={processing}>
                    <CheckIcon className="h-4 w-4" />
                    {isEdit ? "Save changes" : "Create Client"}
                </Button>
            </div>
        </form>
    );
}
