import type { ComponentType, SVGProps } from 'react';
import {
    HomeIcon,
    UserGroupIcon,
    IdentificationIcon,
    BriefcaseIcon,
    Cog6ToothIcon,
    RectangleStackIcon,
    BuildingOffice2Icon,
    ShieldCheckIcon,
    UsersIcon,
    ClockIcon,
    ServerStackIcon,
    DocumentTextIcon,
} from '@heroicons/react/24/outline';

export type IconComponent = ComponentType<SVGProps<SVGSVGElement>>;

export interface NavItem {
    label: string;
    icon?: IconComponent;
    href?: string;
    permission?: string;
    rolesExcept?: string[];
    rolesOnly?: string[];
    external?: boolean;
    hidden?: boolean;
    children?: NavItem[];
}

export const navItems: NavItem[] = [
    {
        label: 'Dashboard',
        icon: HomeIcon,
        href: '/dashboard',
    },
    {
        label: 'Employees',
        icon: BriefcaseIcon,
        href: '/employees',
        permission: 'view employees',
    },
    {
        label: 'Teams',
        icon: UserGroupIcon,
        href: '/teams',
        permission: 'view teams',
    },
    {
        label: 'Clients',
        icon: IdentificationIcon,
        href: '/clients',
        permission: 'view clients',
    },
    {
        label: 'Projects',
        icon: RectangleStackIcon,
        href: '/projects',
    },
];

export const settingsItem: NavItem = {
    label: 'Settings',
    icon: Cog6ToothIcon,
    href: '/settings',
    rolesExcept: ['client'],
};

export const settingsSections: NavItem[] = [
    {
        label: 'Companies',
        icon: BuildingOffice2Icon,
        href: '/companies',
        permission: 'view companies',
    },
    {
        label: 'Departments',
        icon: IdentificationIcon,
        href: '/departments',
        permission: 'view departments',
    },
    {
        label: 'Designation',
        icon: BriefcaseIcon,
        href: '/designations',
        permission: 'view designations',
    },
    {
        label: 'Roles',
        icon: ShieldCheckIcon,
        href: '/roles',
        permission: 'view roles',
    },
    {
        label: 'Users',
        icon: UsersIcon,
        href: '/users',
        permission: 'view users',
    },
    {
        label: 'Activity Log',
        icon: ClockIcon,
        href: '/activity-logs',
        permission: 'view activity logs',
    },
    {
        label: 'System Monitoring',
        icon: ServerStackIcon,
        permission: 'view system monitoring',
        children: [
            {
                label: 'View Log',
                icon: DocumentTextIcon,
                href: '/system-monitoring/log-viewer',
                external: true,
                permission: 'view system monitoring',
            },
        ],
    },
];

function matches(path: string, item: NavItem, base: string): boolean {
    if (!item.href) {
        return false;
    }

    const href = base + item.href;

    return path === href || path.startsWith(href + '/');
}

export function isSettingsPath(path: string, base = ''): boolean {
    return settingsSections
        .flatMap((section) => section.children ?? [section])
        .some((item) => matches(path, item, base));
}

export function settingsTitle(path: string, base = ''): string | null {
    const section = settingsSections
        .flatMap((item) => item.children ?? [item])
        .find((item) => matches(path, item, base));

    return section?.label ?? null;
}
