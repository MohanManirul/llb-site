import ForgotPassword from '@/pages/admin/forgot-password/page';

export default function ClientForgotPassword() {
    return <ForgotPassword action="/forgot-password" loginHref="/login" />;
}
