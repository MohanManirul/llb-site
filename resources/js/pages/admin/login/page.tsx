import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { TextInput, Checkbox, Button } from '@/components/ui';

interface LoginForm {
    email: string;
    password: string;
    remember: boolean;
}

export default function Login({ action = '/admin/login' }) {
    const { flash } = usePage().props;
    const { data, setData, post, processing, errors } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        post(action);
    }

    return (
        <>
            <Head title="Login" />

            <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 px-4">
                <div className="w-full max-w-sm">
                    <Link
                        href="/"
                        className="flex items-center justify-center gap-2 mb-6"
                    >
                        <img
                            src="/assets/logos/boneek.png"
                            alt="Boneek Logo"
                            className="h-auto w-45"
                        />
                    </Link>

                    <form
                        onSubmit={submit}
                        className="bg-white border border-gray-200 rounded-xl shadow-sm p-8"
                    >
                        <h1 className="text-xl font-semibold text-[#002044] mb-1">
                            Sign in
                        </h1>
                        <p className="text-sm text-gray-500 mb-6">
                            Welcome back. Please enter your details.
                        </p>

                        {flash?.success && (
                            <div className="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">
                                {flash.success}
                            </div>
                        )}

                        <div className="space-y-4">
                            <TextInput
                                label="Email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                error={errors.email}
                                autoFocus
                            />

                            <TextInput
                                label="Password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                error={errors.password}
                            />
                        </div>

                        <div className="mt-4 flex items-center justify-between">
                            <Checkbox
                                label="Remember me"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                            />
                            <Link
                                href={
                                    action === '/admin/login'
                                        ? '/admin/forgot-password'
                                        : '/forgot-password'
                                }
                                className="text-sm font-medium text-indigo-600 hover:underline"
                            >
                                Forgot password?
                            </Link>
                        </div>

                        <Button
                            type="submit"
                            disabled={processing}
                            className="mt-6 w-full"
                        >
                            {processing ? 'Signing in…' : 'Sign in'}
                        </Button>
                    </form>
                </div>
            </div>
        </>
    );
}
