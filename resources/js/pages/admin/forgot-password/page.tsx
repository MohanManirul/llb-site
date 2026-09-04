import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { TextInput, Button } from '@/components/ui';

interface ForgotPasswordForm {
    email: string;
}

interface ForgotPasswordProps {
    action?: string;
    loginHref?: string;
}

export default function ForgotPassword({
    action = '/admin/forgot-password',
    loginHref = '/admin/login',
}: ForgotPasswordProps) {
    const { flash } = usePage().props;
    const { data, setData, post, processing, errors } = useForm<ForgotPasswordForm>({ email: '' });

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        post(action);
    }

    return (
        <>
            <Head title="Forgot password" />

            <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 px-4">
                <div className="w-full max-w-sm">
                    <Link
                        href="/"
                        className="flex items-center justify-center gap-2 mb-6"
                    >
                        <img
                            src="/llb.jpg"
                            alt="LLB Logo Logo"
                            className="h-auto w-45"
                        />
                    </Link>

                    <form
                        onSubmit={submit}
                        className="bg-white border border-gray-200 rounded-xl shadow-sm p-8"
                    >
                        <h1 className="text-xl font-semibold text-[#002044] mb-1">
                            Forgot password
                        </h1>
                        <p className="text-sm text-gray-500 mb-6">
                            Enter your email and we&apos;ll send you a reset link.
                        </p>

                        {errors.email && (
                            <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                {errors.email}
                            </div>
                        )}

                        {!errors.email && flash?.success && (
                            <div className="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">
                                {flash.success}
                            </div>
                        )}

                        <TextInput
                            label="Email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            autoFocus
                        />

                        <Button
                            type="submit"
                            disabled={processing}
                            className="mt-6 w-full"
                        >
                            {processing ? 'Sending…' : 'Send reset link'}
                        </Button>

                        <p className="text-sm text-gray-500 mt-6 text-center">
                            Remembered it?{' '}
                            <Link
                                href={loginHref}
                                className="text-indigo-600 hover:underline font-medium"
                            >
                                Back to sign in
                            </Link>
                        </p>
                    </form>
                </div>
            </div>
        </>
    );
}
