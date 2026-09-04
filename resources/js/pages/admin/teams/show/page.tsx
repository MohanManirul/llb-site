import { useEffect, useState, type ReactNode } from "react";
import { Link } from "@inertiajs/react";
import {
    PencilSquareIcon,
    ArrowPathIcon,
    UserIcon,
    StarIcon,
} from "@heroicons/react/24/outline";

import DashboardLayout from "@/components/common/DashboardLayout";
import PageHeader from '@/components/common/PageHeader';
import { Button } from "@/components/ui";
import api from "@/lib/api-client";
import { flash, errorMessage } from "@/lib/flash";
import type { TeamDetail } from "../types";

interface InfoRowProps {
    label: string;
    children: ReactNode;
}

function InfoRow({ label, children }: InfoRowProps) {
    return (
        <div>
            <dt className="text-xs font-medium uppercase tracking-wide text-gray-500">
                {label}
            </dt>
            <dd className="mt-1 text-sm text-gray-900">{children}</dd>
        </div>
    );
}

interface MemberAvatarProps {
    image?: string | null;
    name?: string | null;
}

function MemberAvatar({ image, name }: MemberAvatarProps) {
    if (!image) {
        return (
            <span className="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100">
                <UserIcon className="h-5 w-5 text-gray-400" />
            </span>
        );
    }

    return (
        <img
            src={image}
            alt={name ?? ""}
            className="h-9 w-9 shrink-0 rounded-full border border-gray-200 object-cover"
        />
    );
}

interface TeamShowProps {
    teamId: string;
}

export default function TeamShow({ teamId }: TeamShowProps) {
    const [team, setTeam] = useState<TeamDetail | null>(null);

    useEffect(() => {
        let cancelled = false;

        api.get(`/admin/teams/${teamId}`)
            .then(({ data }) => {
                if (!cancelled) setTeam(data.result);
            })
            .catch((error) => {
                flash.error(errorMessage(error, "Could not load the team."));
            });

        return () => {
            cancelled = true;
        };
    }, [teamId]);

    if (!team) {
        return (
            <>
            <PageHeader
                title="Team"
                backHref="/admin/teams"
                backLabel="Back to teams"
            />

                <div className="flex justify-center p-10">
                    <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                </div>
        </>
        );
    }

    const members = team.members ?? [];

    return (
        <>
            <PageHeader
                title="Team"
                backHref="/admin/teams"
                backLabel="Back to teams" action={ <Link href={`/admin/teams/${team.id}/edit`}> <Button size="sm"> <PencilSquareIcon className="h-4 w-4" /> Edit </Button> </Link> } />

            <div className="w-full space-y-6">
                <div className="rounded-card border border-hairline bg-white shadow-sm">
                    <div className="flex items-start justify-between border-b border-gray-100 p-6">
                        <div>
                            <h2 className="text-lg font-semibold text-gray-900">
                                {team.name}
                            </h2>
                            {team.description && (
                                <p className="mt-1 text-sm text-gray-500">
                                    {team.description}
                                </p>
                            )}
                        </div>
                        <span
                            className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${
                                team.is_active
                                    ? "bg-green-100 text-green-700"
                                    : "bg-red-100 text-red-700"
                            }`}
                        >
                            {team.is_active ? "Active" : "Inactive"}
                        </span>
                    </div>

                    <dl className="grid grid-cols-1 gap-x-6 gap-y-5 p-6 sm:grid-cols-2">
                        <InfoRow label="Company">
                            {team.company_name ?? "—"}
                        </InfoRow>
                        <InfoRow label="Department">
                            {team.department_name ?? "—"}
                        </InfoRow>
                        <InfoRow label="Members">{members.length}</InfoRow>
                    </dl>
                </div>

                <div className="rounded-card border border-hairline bg-white shadow-sm">
                    <div className="border-b border-gray-100 px-6 py-4">
                        <h3 className="text-sm font-semibold text-gray-900">
                            Members ({members.length})
                        </h3>
                    </div>

                    <ul className="divide-y divide-gray-100">
                        {members.length === 0 && (
                            <li className="px-6 py-4 text-sm text-gray-400">
                                No members yet.
                            </li>
                        )}
                        {members.map((member) => (
                            <li
                                key={member.id}
                                className="flex items-center justify-between px-6 py-3"
                            >
                                <div className="flex items-center gap-3">
                                    <MemberAvatar
                                        image={member.thumbnail_url}
                                        name={member.name}
                                    />
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">
                                            {member.name}
                                        </p>
                                        {member.designation && (
                                            <p className="text-xs text-gray-500">
                                                {member.designation}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                {member.role === "leader" ? (
                                    <span className="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700">
                                        <StarIcon className="h-3.5 w-3.5" />
                                        Leader
                                    </span>
                                ) : (
                                    <span className="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
                                        Member
                                    </span>
                                )}
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </>
    );
}

TeamShow.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
