import { EnvelopeIcon, PhoneIcon, ScaleIcon } from '@heroicons/react/24/outline';
import useTranslation from '@/hooks/useTranslation';
import useSite from '@/hooks/useSite';
import { ADMIN_LOGIN_HREF } from '@/config/site';
import { FacebookIcon, WhatsAppIcon } from './BrandIcons';
import { BanyanSilhouette } from './motifs';

export default function PublicFooter() {
    const { t } = useTranslation();
    const site = useSite();

    return (
        <footer className="relative overflow-hidden bg-brand text-white/70">
            <div className="h-1 bg-linear-to-r from-banyan via-brass to-brand" />
            <BanyanSilhouette className="pointer-events-none absolute -bottom-4 right-4 h-36 w-auto text-white opacity-[0.07] sm:right-10" />

            <div className="relative mx-auto flex w-full max-w-300 flex-col gap-6 px-4 py-8 text-sm sm:flex-row sm:items-start sm:justify-between md:px-6">
                <div className="max-w-md">
                    <p className="flex items-center gap-2 font-semibold text-white">
                        {site.logo_url ? (
                            <img
                                src={site.logo_url}
                                alt=""
                                className="h-5 w-5 rounded-chip object-cover"
                            />
                        ) : (
                            <ScaleIcon className="h-4 w-4 text-brass" />
                        )}
                        {site.siteName}
                    </p>
                    <p className="mt-1">{site.slogan}</p>
                    <p className="mt-2 text-xs text-brass/90">{t('footer.tagline')}</p>
                </div>

                <div className="flex flex-col gap-3 sm:items-end">
                    <div className="flex flex-wrap items-center gap-3">
                        {site.whatsapp_url && (
                            <a
                                href={site.whatsapp_url}
                                target="_blank"
                                rel="noreferrer noopener"
                                className="inline-flex items-center gap-1.5 rounded-control bg-white/10 px-3 py-1.5 text-xs font-medium text-white hover:bg-white/20"
                            >
                                <WhatsAppIcon className="h-4 w-4" />
                                {t('footer.whatsapp')}
                            </a>
                        )}

                        {site.facebook_url && (
                            <a
                                href={site.facebook_url}
                                target="_blank"
                                rel="noreferrer noopener"
                                className="inline-flex items-center gap-1.5 rounded-control bg-white/10 px-3 py-1.5 text-xs font-medium text-white hover:bg-white/20"
                            >
                                <FacebookIcon className="h-4 w-4" />
                                {t('footer.facebook')}
                            </a>
                        )}
                    </div>

                    {(site.email || site.phone) && (
                        <div className="flex flex-wrap items-center gap-4 text-xs">
                            {site.email && (
                                <a
                                    href={`mailto:${site.email}`}
                                    className="inline-flex items-center gap-1.5 hover:text-white"
                                >
                                    <EnvelopeIcon className="h-4 w-4" />
                                    {site.email}
                                </a>
                            )}
                            {site.phone && (
                                <a
                                    href={`tel:${site.phone}`}
                                    className="inline-flex items-center gap-1.5 hover:text-white"
                                >
                                    <PhoneIcon className="h-4 w-4" />
                                    {site.phone}
                                </a>
                            )}
                        </div>
                    )}

                    <div className="flex items-center gap-4 text-xs">
                        <span>
                            © {new Date().getFullYear()} · {t('footer.rights')}
                        </span>
                        <a href={ADMIN_LOGIN_HREF} className="hover:text-white">
                            {t('nav.admin_login')}
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    );
}
