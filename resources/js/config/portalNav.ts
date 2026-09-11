import type { ComponentType, SVGProps } from 'react';
import {
    BookOpenIcon,
    CalendarDaysIcon,
    ClipboardDocumentCheckIcon,
    MegaphoneIcon,
    UserCircleIcon,
} from '@heroicons/react/24/outline';
import type { TranslationKey } from '@/lib/i18n';

export type PortalIcon = ComponentType<SVGProps<SVGSVGElement>>;

export interface PortalNavItem {
    labelKey: TranslationKey;
    href: string;
    icon: PortalIcon;
}

export const studentPortalNav: PortalNavItem[] = [
    { labelKey: 'nav.college_routine', href: '/account/college/routine', icon: CalendarDaysIcon },
    { labelKey: 'nav.college_notices', href: '/account/college/notices', icon: MegaphoneIcon },
    { labelKey: 'nav.college_notes', href: '/account/college/notes', icon: BookOpenIcon },
    { labelKey: 'nav.my_attempts', href: '/account/attempts', icon: ClipboardDocumentCheckIcon },
    { labelKey: 'nav.profile', href: '/account/profile', icon: UserCircleIcon },
];

export const teacherPortalNav: PortalNavItem[] = [
    { labelKey: 'teacher.nav_routines', href: '/teacher/routines', icon: CalendarDaysIcon },
    { labelKey: 'teacher.nav_notices', href: '/teacher/notices', icon: MegaphoneIcon },
    { labelKey: 'teacher.nav_notes', href: '/teacher/notes', icon: BookOpenIcon },
    { labelKey: 'teacher.nav_profile', href: '/teacher/profile', icon: UserCircleIcon },
];
