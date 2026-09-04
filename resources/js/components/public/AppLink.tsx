import { Link, type InertiaLinkProps } from '@inertiajs/react';
import useTranslation from '@/hooks/useTranslation';

export interface AppLinkProps extends Omit<InertiaLinkProps, 'href'> {
    href: string;
}

export default function AppLink({ href, ...props }: AppLinkProps) {
    const { localeHref } = useTranslation();

    return <Link {...props} href={localeHref(href)} />;
}
