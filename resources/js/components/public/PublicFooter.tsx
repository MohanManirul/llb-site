import useTranslation from '@/hooks/useTranslation';
import { ADMIN_LOGIN_HREF, SITE_NAME, SITE_NAME_BN } from '@/config/site';

export default function PublicFooter() {
    const { t, isBn } = useTranslation();

    return (
        <footer className="border-t border-hairline bg-white">
            <div className="mx-auto flex w-full max-w-300 flex-col gap-3 px-4 py-6 text-sm text-ink-muted sm:flex-row sm:items-center sm:justify-between md:px-6">
                <div>
                    <p className="font-semibold text-brand">{isBn ? SITE_NAME_BN : SITE_NAME}</p>
                    <p className="mt-0.5">{t('footer.about')}</p>
                </div>

                <div className="flex items-center gap-4">
                    <span>
                        © {new Date().getFullYear()} · {t('footer.rights')}
                    </span>
                    <a href={ADMIN_LOGIN_HREF} className="hover:text-brand">
                        {t('nav.admin_login')}
                    </a>
                </div>
            </div>
        </footer>
    );
}
