import ResetPassword from '@/pages/admin/reset-password/page';

interface ClientResetPasswordProps {
    token: string;
    email?: string;
}

export default function ClientResetPassword({ token, email }: ClientResetPasswordProps) {
    return <ResetPassword token={token} email={email} action="/reset-password" />;
}
