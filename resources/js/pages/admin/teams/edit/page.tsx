import { useEffect, useState, type ReactNode } from "react";
import { ArrowPathIcon } from "@heroicons/react/24/outline";
import DashboardLayout from "@/components/common/DashboardLayout";
import PageHeader from '@/components/common/PageHeader';
import TeamFormComponent from "../components/TeamFormComponent";
import api from "@/lib/api-client";
import { flash, errorMessage } from "@/lib/flash";
import type { TeamDetail } from "../types";

interface TeamEditProps {
    teamId: string;
}

export default function TeamEdit({ teamId }: TeamEditProps) {
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

    return (
        <>
            <PageHeader
                title="Edit Team" />

            {team ? (
                <TeamFormComponent team={team} />
            ) : (
                <div className="flex justify-center p-10">
                    <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                </div>
            )}
        </>
    );
}

TeamEdit.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
