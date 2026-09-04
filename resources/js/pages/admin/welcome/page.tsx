import { Head, Link } from '@inertiajs/react';
import type { ComponentType, SVGProps } from 'react';
import {
    UsersIcon,
    BriefcaseIcon,
    ChartBarIcon,
    ShieldCheckIcon,
    BoltIcon,
    LockClosedIcon,
    ArrowRightIcon,
} from '@heroicons/react/24/outline';

export default function Welcome() {
    const year = new Date().getFullYear();

    return (
        <>
            <Head title="ReactCRM — Manage customers, close more deals" />

            <div className="flex min-h-screen flex-col bg-white text-gray-800">
                <header className="sticky top-0 z-10 border-b border-gray-200 bg-white/90 backdrop-blur">
                    <nav className="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
                        <Link href="/" className="flex items-center gap-2">
                            <span className="inline-flex h-9 w-9 items-center justify-center rounded-md bg-brand font-bold text-white">
                                C
                            </span>
                            <span className="text-lg font-bold uppercase tracking-wider text-brand">
                                ReactCRM
                            </span>
                        </Link>

                        <div className="flex items-center gap-2">
                            <a
                                href="#features"
                                className="hidden px-3 py-2 text-sm font-medium text-gray-600 hover:text-brand sm:inline"
                            >
                                Features
                            </a>
                            <Link
                                href="/login"
                                className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
                            >
                                Sign in
                            </Link>
                        </div>
                    </nav>
                </header>

                <main className="flex-1">
                    <section className="mx-auto max-w-6xl px-6 pb-16 pt-20 text-center">
                        <span className="inline-block rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-indigo-600">
                            Customer Relationship Management
                        </span>

                        <h1 className="mt-6 text-4xl font-bold leading-tight text-brand sm:text-5xl">
                            Manage customers,
                            <br className="hidden sm:block" /> close more deals.
                        </h1>

                        <p className="mx-auto mt-5 max-w-2xl text-lg text-gray-500">
                            Track leads, organize contacts, and keep your whole
                            team aligned — with role-based dashboards so everyone
                            sees exactly what matters to them.
                        </p>

                        <div className="mt-9 flex items-center justify-center gap-3">
                            <Link
                                href="/login"
                                className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-indigo-500"
                            >
                                Sign in to your workspace
                                <ArrowRightIcon className="h-4 w-4" />
                            </Link>
                            <a
                                href="#features"
                                className="rounded-lg border border-gray-300 px-6 py-3 font-semibold text-gray-700 transition hover:border-gray-400 hover:bg-gray-50"
                            >
                                Explore features
                            </a>
                        </div>

                        <p className="mt-4 text-sm text-gray-400">
                            Accounts are provisioned by your administrator.
                        </p>
                    </section>

                    <section className="border-y border-gray-100 bg-gray-50">
                        <div className="mx-auto grid max-w-6xl grid-cols-2 gap-6 px-6 py-10 sm:grid-cols-4">
                            {stats.map((s) => (
                                <div key={s.label} className="text-center">
                                    <p className="text-3xl font-bold text-brand">
                                        {s.value}
                                    </p>
                                    <p className="mt-1 text-sm text-gray-500">
                                        {s.label}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section id="features" className="mx-auto max-w-6xl px-6 py-20">
                        <div className="mx-auto max-w-2xl text-center">
                            <h2 className="text-3xl font-bold text-brand">
                                Everything you need to run your pipeline
                            </h2>
                            <p className="mt-3 text-gray-500">
                                A clean, focused workspace your team can pick up
                                in minutes.
                            </p>
                        </div>

                        <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {features.map((f) => {
                                const Icon = f.icon;
                                return (
                                    <div
                                        key={f.title}
                                        className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md"
                                    >
                                        <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                            <Icon className="h-6 w-6" />
                                        </div>
                                        <h3 className="mt-4 font-semibold text-brand">
                                            {f.title}
                                        </h3>
                                        <p className="mt-2 text-sm leading-relaxed text-gray-500">
                                            {f.body}
                                        </p>
                                    </div>
                                );
                            })}
                        </div>
                    </section>

                    <section className="mx-auto max-w-6xl px-6 py-20">
                        <div className="rounded-2xl bg-brand px-8 py-14 text-center">
                            <h2 className="text-3xl font-bold text-white">
                                Ready to get to work?
                            </h2>
                            <p className="mx-auto mt-3 max-w-xl text-indigo-100">
                                Sign in to your workspace and pick up right where
                                your team left off.
                            </p>
                            <Link
                                href="/login"
                                className="mt-8 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 font-semibold text-white transition hover:bg-indigo-500"
                            >
                                Sign in
                                <ArrowRightIcon className="h-4 w-4" />
                            </Link>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-gray-200 bg-white">
                    <div className="mx-auto max-w-6xl px-6 py-12">
                        <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="inline-flex h-9 w-9 items-center justify-center rounded-md bg-brand font-bold text-white">
                                        C
                                    </span>
                                    <span className="text-lg font-bold uppercase tracking-wider text-brand">
                                        ReactCRM
                                    </span>
                                </div>
                                <p className="mt-4 max-w-xs text-sm text-gray-500">
                                    A simple, role-based CRM for managing
                                    customers, leads and deals.
                                </p>
                            </div>

                            {footerColumns.map((col) => (
                                <div key={col.title}>
                                    <h4 className="text-sm font-semibold uppercase tracking-wide text-gray-800">
                                        {col.title}
                                    </h4>
                                    <ul className="mt-4 space-y-2">
                                        {col.links.map((link) => (
                                            <li key={link}>
                                                <a
                                                    href="#"
                                                    className="text-sm text-gray-500 hover:text-brand"
                                                >
                                                    {link}
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>

                        <div className="mt-10 border-t border-gray-100 pt-6 text-center text-sm text-gray-400">
                            © {year} ReactCRM. Built with Laravel, Inertia &amp;
                            React.
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}

interface Stat {
    value: string;
    label: string;
}

interface Feature {
    icon: ComponentType<SVGProps<SVGSVGElement>>;
    title: string;
    body: string;
}

interface FooterColumn {
    title: string;
    links: string[];
}

const stats: Stat[] = [
    { value: '1,200+', label: 'Customers managed' },
    { value: '320+', label: 'Active leads' },
    { value: '3', label: 'Built-in roles' },
    { value: '99.9%', label: 'Uptime' },
];

const features: Feature[] = [
    {
        icon: UsersIcon,
        title: 'Contacts',
        body: 'Keep every customer and lead organized in one searchable, sortable place.',
    },
    {
        icon: BriefcaseIcon,
        title: 'Pipeline',
        body: 'Track deals from first contact to close so nothing slips through the cracks.',
    },
    {
        icon: ChartBarIcon,
        title: 'Dashboards',
        body: 'Role-scoped reports give each person the numbers that matter to them.',
    },
    {
        icon: ShieldCheckIcon,
        title: 'Roles & permissions',
        body: 'Fine-grained access for admins, agents and clients out of the box.',
    },
    {
        icon: BoltIcon,
        title: 'Fast filtering',
        body: 'Search, filter and sort long record lists without losing your place.',
    },
    {
        icon: LockClosedIcon,
        title: 'Secure by default',
        body: 'Session auth, password resets and admin-provisioned accounts.',
    },
];

const footerColumns: FooterColumn[] = [
    { title: 'Product', links: ['Features', 'Dashboards', 'Security'] },
    { title: 'Company', links: ['About', 'Careers', 'Contact'] },
    { title: 'Legal', links: ['Privacy', 'Terms'] },
];
