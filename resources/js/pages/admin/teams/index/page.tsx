import { useMemo, useRef, useState, type ChangeEvent, type ReactNode } from "react";
import { Link, router } from "@inertiajs/react";
import { PlusIcon, PencilSquareIcon } from "@heroicons/react/24/outline";

import DashboardLayout from "@/components/common/DashboardLayout";
import PageHeader from '@/components/common/PageHeader';
import {
    Button,
    ConfirmationModal,
    DataTable,
    DeleteButton,
    SearchableSelect,
    TableFilters,
    TableSelect,
} from "@/components/ui";
import useDeleteResource from "@/hooks/useDeleteResource";
import usePermissions from "@/hooks/usePermissions";
import useResourceIndex from "@/hooks/useResourceIndex";
import { formatDate } from "@/lib/format";
import MembersCell from "./components/MembersCell";
import type { TeamMemberOption, TeamOption, TeamRow } from "../types";

interface TeamColumn {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: TeamRow) => ReactNode;
}

export default function TeamsIndex() {
    const { can } = usePermissions();
    const canDelete = can("delete teams");

    const [isActive, setIsActive] = useState("");
    const [role, setRole] = useState("");

    const [teamId, setTeamId] = useState("");
    const [teamOption, setTeamOption] = useState<TeamOption | null>(null);
    const [companyId, setCompanyId] = useState("");
    const [companyOption, setCompanyOption] = useState<TeamOption | null>(null);
    const [departmentId, setDepartmentId] = useState("");
    const [departmentOption, setDepartmentOption] = useState<TeamOption | null>(null);
    const [employeeId, setEmployeeId] = useState("");
    const [employeeOption, setEmployeeOption] = useState<TeamMemberOption | null>(null);

    const { tableProps, refetch } = useResourceIndex<TeamRow>({
        url: "/admin/teams",
        storageKey: "teams",
        filters: {
            team_id: teamId,
            is_active: isActive,
            company_id: companyId,
            department_id: departmentId,
            role,
            employee_id: employeeId,
        },
        errorMessage: "Could not load teams.",
    });

    const remove = useDeleteResource<TeamRow>({
        url: (team) => `/admin/teams/${team.id}`,
        onDeleted: refetch,
        successMessage: "Team deleted successfully.",
        errorMessage: "Could not delete this team.",
    });

    const filterByMemberRef = useRef<((option: TeamMemberOption) => void) | undefined>(undefined);
    filterByMemberRef.current = (option) => {
        setRole("member");
        setEmployeeId(String(option.value));
        setEmployeeOption(option);
    };

    const selectedMemberRef = useRef<TeamMemberOption | null>(null);
    selectedMemberRef.current = employeeOption;

    const columns = useMemo<TeamColumn[]>(
        () => [
            {
                key: "company_name",
                header: "Company",
                render: (row) => row.company_name ?? "—",
                sortable: true,
            },
            {
                key: "department_name",
                header: "Department",
                render: (row) => row.department_name ?? "—",
                sortable: true,
            },
            {
                key: "name",
                header: "Team",
                className: "font-medium",
                sortable: true,
            },
            {
                key: "leader",
                header: "Leader",
                sortable: true,
                render: (row) => row.leader?.name ?? "—",
            },
            {
                key: "members_count",
                header: "Members",
                sortable: true,
                render: (row) => (
                    <MembersCell
                        members={row.members ?? []}
                        count={row.members_count ?? 0}
                        selectedMember={selectedMemberRef.current}
                        onSelectMember={(option) =>
                            filterByMemberRef.current?.(option)
                        }
                    />
                ),
            },
            {
                key: "is_active",
                header: "Status",
                sortable: true,
                render: (row) => (
                    <span
                        className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${
                            row.is_active
                                ? "bg-green-100 text-green-700"
                                : "bg-red-100 text-red-700"
                        }`}
                    >
                        {row.is_active ? "Active" : "Inactive"}
                    </span>
                ),
            },
            {
                key: "created_at",
                header: "Created",
                sortable: true,
                render: (row) => formatDate(row.created_at),
            },
            {
                key: "actions",
                header: "Actions",
                className: "text-right",
                render: (row) => (
                    <div className="flex items-center justify-end gap-3">
                        <Link
                            href={`/admin/teams/${row.id}/edit`}
                            className="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800"
                            onClick={(event) => event.stopPropagation()}
                        >
                            <PencilSquareIcon className="h-4 w-4" />
                            Edit
                        </Link>

                        {canDelete && (
                            <DeleteButton onDelete={() => remove.request(row)} />
                        )}
                    </div>
                ),
            },
        ],
        [canDelete, remove.request],
    );

    function changeRole(value: string) {
        setRole(value);
        setEmployeeId("");
        setEmployeeOption(null);
    }

    function clearFilters() {
        setTeamId("");
        setTeamOption(null);
        setIsActive("");
        setCompanyId("");
        setCompanyOption(null);
        setDepartmentId("");
        setDepartmentOption(null);
        setRole("");
        setEmployeeId("");
        setEmployeeOption(null);
    }

    return (
        <>
            <PageHeader
                title="Team"
            action={
                <Link href="/admin/teams/create">
                    <Button size="sm">
                        <PlusIcon className="h-4 w-4" />
                        Create
                    </Button>
                </Link>
                }
            />

            <div className="flex flex-col">
                <DataTable
                    columns={columns}
                    {...tableProps}
                    onRowClick={(row: TeamRow) => router.visit(`/admin/teams/${row.id}`)}
                    filters={
                        <TableFilters
                            activeCount={
                                (teamId ? 1 : 0) +
                                (isActive ? 1 : 0) +
                                (companyId ? 1 : 0) +
                                (departmentId ? 1 : 0) +
                                (role ? 1 : 0) +
                                (employeeId ? 1 : 0)
                            }
                            onClear={clearFilters}
                        >
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Company
                                </label>

                                <SearchableSelect
                                    value={companyId}
                                    onChange={(value: string, option: TeamOption | null) => {
                                        setCompanyId(value);
                                        setCompanyOption(option);
                                    }}
                                    fetchUrl="/v1/admin/companies/search"
                                    initialOptions={[]}
                                    selectedOption={companyOption}
                                    placeholder="All Companies"
                                    searchPlaceholder="Search Companies"
                                />
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Department
                                </label>

                                <SearchableSelect
                                    value={departmentId}
                                    onChange={(value: string, option: TeamOption | null) => {
                                        setDepartmentId(value);
                                        setDepartmentOption(option);
                                    }}
                                    fetchUrl="/v1/admin/departments/search"
                                    initialOptions={[]}
                                    selectedOption={departmentOption}
                                    placeholder="All Departments"
                                    searchPlaceholder="Search Departments"
                                />
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Team
                                </label>

                                <SearchableSelect
                                    value={teamId}
                                    onChange={(value: string, option: TeamOption | null) => {
                                        setTeamId(value);
                                        setTeamOption(option);
                                    }}
                                    fetchUrl="/v1/admin/teams/search"
                                    initialOptions={[]}
                                    selectedOption={teamOption}
                                    placeholder="All Teams"
                                    searchPlaceholder="Search Teams"
                                />
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Role
                                </label>

                                <TableSelect
                                    value={role}
                                    onChange={(e: ChangeEvent<HTMLSelectElement>) => changeRole(e.target.value)}
                                >
                                    <option value="">All Roles</option>

                                    <option value="leader">Leader</option>

                                    <option value="member">Member</option>
                                </TableSelect>
                            </div>

                            {(role === "leader" || role === "member") && (
                                <div>
                                    <label className="mb-1 block text-xs font-medium text-gray-700">
                                        {role === "leader"
                                            ? "Leader"
                                            : "Member"}
                                    </label>

                                    <SearchableSelect
                                        key={role}
                                        value={employeeId}
                                        onChange={(value: string, option: TeamMemberOption | null) => {
                                            setEmployeeId(value);
                                            setEmployeeOption(option);
                                        }}
                                        fetchUrl={`/v1/admin/teams/members/search?role=${role}`}
                                        initialOptions={[]}
                                        selectedOption={employeeOption}
                                        placeholder={
                                            role === "leader"
                                                ? "All Leaders"
                                                : "All Members"
                                        }
                                        searchPlaceholder={
                                            role === "leader"
                                                ? "Search Leaders"
                                                : "Search Members"
                                        }
                                    />
                                </div>
                            )}

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Status
                                </label>

                                <TableSelect
                                    value={isActive}
                                    onChange={(e: ChangeEvent<HTMLSelectElement>) => setIsActive(e.target.value)}
                                >
                                    <option value="">All Status</option>

                                    <option value="1">Active</option>

                                    <option value="0">Inactive</option>
                                </TableSelect>
                            </div>
                        </TableFilters>
                    }
                />
            </div>

            <ConfirmationModal
                show={remove.pending !== null}
                onClose={remove.cancel}
                onConfirm={remove.confirm}
                processing={remove.deleting}
                title="Delete team"
                confirmText="Delete"
            >
                Are you sure you want to delete{" "}
                <span className="font-medium">{remove.pending?.name}</span>?
                This action cannot be undone.
            </ConfirmationModal>
        </>
    );
}

TeamsIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
