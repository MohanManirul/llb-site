import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { TextInput, Button } from '@/components/ui';

interface ResetPasswordForm {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
}

interface ResetPasswordProps {
    token: string;
    email?: string;
    action?: string;
}

export default function ResetPassword({
    token,
    email,
    action = '/admin/reset-password',
}: ResetPasswordProps) {
    const { data, setData, post, processing, errors } = useForm<ResetPasswordForm>({
        token,
        email: email ?? '',
        password: '',
        password_confirmation: '',
    });

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        post(action);
    }

    return (
        <>
            <Head title="Reset password" />

            <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 px-4">
                <div className="w-full max-w-sm">
                    <Link
                        href="/"
                        className="flex items-center justify-center gap-2 mb-6"
                    >
                        <span className="inline-flex items-center justify-center w-10 h-10 rounded-md bg-[#002044] text-white font-bold">
                            C
                        </span>
                        <span className="text-xl font-bold tracking-wider uppercase text-[#002044]">
                            CRM
                        </span>
                    </Link>

                    <form
                        onSubmit={submit}
                        className="bg-white border border-gray-200 rounded-xl shadow-sm p-8"
                    >
                        <h1 className="text-xl font-semibold text-[#002044] mb-1">
                            Reset password
                        </h1>
                        <p className="text-sm text-gray-500 mb-6">
                            Choose a new password for your account.
                        </p>

                        <div className="space-y-4">
                            <TextInput
                                label="Email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                error={errors.email}
                            />
                            <TextInput
                                label="New password"
                                type="password"
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                error={errors.password}
                                autoFocus
                            />
                            <TextInput
                                label="Confirm password"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) =>
                                    setData('password_confirmation', e.target.value)
                                }
                            />
                        </div>

                        <Button
                            type="submit"
                            disabled={processing}
                            className="mt-6 w-full"
                        >
                            {processing ? 'Resetting…' : 'Reset password'}
                        </Button>
                    </form>
                </div>
            </div>
        </>
    );
}
