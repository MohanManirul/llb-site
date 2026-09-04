import { ScaleIcon } from '@heroicons/react/24/outline';
import useTranslation from '@/hooks/useTranslation';
import { ADMIN_LOGIN_HREF, SITE_NAME, SITE_NAME_BN } from '@/config/site';
import { BanyanSilhouette } from './motifs';

export default function PublicFooter() {
    const { t, isBn } = useTranslation();

    return (
        <footer className="relative overflow-hidden bg-brand text-white/70">
            <div className="h-1 bg-linear-to-r from-banyan via-brass to-brand" />
            <BanyanSilhouette className="pointer-events-none absolute -bottom-4 right-4 h-36 w-auto text-white opacity-[0.07] sm:right-10" />

            <div className="relative mx-auto flex w-full max-w-300 flex-col gap-4 px-4 py-8 text-sm sm:flex-row sm:items-center sm:justify-between md:px-6">
                <div className="max-w-md">
                    <p className="flex items-center gap-2 font-semibold text-white">
                        <ScaleIcon className="h-4 w-4 text-brass" />
                        {isBn ? SITE_NAME_BN : SITE_NAME}
                    </p>
                    <p className="mt-1">{t('footer.about')}</p>
                    <p className="mt-2 text-xs text-brass/90">{t('footer.tagline')}</p>
                </div>

                <div className="flex items-center gap-4">
                    <span>
                        © {new Date().getFullYear()} · {t('footer.rights')}
                    </span>
                    <a href={ADMIN_LOGIN_HREF} className="hover:text-white">
                        {t('nav.admin_login')}
                    </a>
                </div>
            </div>
        </footer>
    );
}
