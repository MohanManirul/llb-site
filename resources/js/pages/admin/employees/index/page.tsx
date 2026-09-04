import { useMemo, useState, type ChangeEvent, type ReactNode } from "react";
import { Link } from "@inertiajs/react";
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
import EmployeeViewModal from "../components/EmployeeViewModal";
import useDeleteResource from "@/hooks/useDeleteResource";
import usePermissions from "@/hooks/usePermissions";
import useResourceIndex from "@/hooks/useResourceIndex";
import { formatDate } from "@/lib/format";
import type { Employee, EmployeeOption } from "../types";

interface EmployeeColumn {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: Employee) => ReactNode;
}

export default function EmployeesIndex() {
    const { can } = usePermissions();
    const canDelete = can("delete employees");

    const [viewingEmployee, setViewingEmployee] = useState<Employee | null>(null);

    const [isActive, setIsActive] = useState("");
    const [companyId, setCompanyId] = useState("");
    const [departmentId, setDepartmentId] = useState("");

    const [companyOption, setCompanyOption] = useState<EmployeeOption | null>(null);
    const [departmentOption, setDepartmentOption] = useState<EmployeeOption | null>(null);

    const { tableProps, refetch } = useResourceIndex<Employee>({
        url: "/admin/employees",
        storageKey: "employees",
        filters: {
            is_active: isActive,
            company_id: companyId,
            department_id: departmentId,
        },
        errorMessage: "Could not load employees.",
    });

    const remove = useDeleteResource<Employee>({
        url: (employee) => `/admin/employees/${employee.id}`,
        onDeleted: refetch,
        successMessage: "Employee deleted successfully.",
        errorMessage: "Could not delete this employee.",
    });

    const columns = useMemo<EmployeeColumn[]>(
        () => [
            {
                key: "company_name",
                header: "Company",
                render: (row) => row.company_name ?? "—",
            },
            {
                key: "department_name",
                header: "Department",
                render: (row) => row.department_name ?? "—",
            },
            {
                key: "name",
                header: "Employee",
                sortable: true,
            },
            {
                key: "designation",
                header: "Designation",
                sortable: true,
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
                            href={`/admin/employees/${row.id}/edit`}
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

    function changeCompany(value: string, option: EmployeeOption | null) {
        setCompanyId(value);
        setCompanyOption(value ? option : null);
        setDepartmentId("");
        setDepartmentOption(null);
    }

    function changeDepartment(value: string, option: EmployeeOption | null) {
        setDepartmentId(value);
        setDepartmentOption(value ? option : null);
    }

    function clearFilters() {
        setIsActive("");
        setCompanyId("");
        setCompanyOption(null);
        setDepartmentId("");
        setDepartmentOption(null);
    }

    return (
        <>
            <PageHeader
                title="Employee"
            action={
                <Link href="/admin/employees/create">
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
                    onRowClick={(row: Employee) => setViewingEmployee(row)}
                    filters={
                        <TableFilters
                            activeCount={
                                (isActive ? 1 : 0) +
                                (companyId ? 1 : 0) +
                                (departmentId ? 1 : 0)
                            }
                            onClear={clearFilters}
                        >
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Company
                                </label>

                                <SearchableSelect
                                    value={companyId}
                                    onChange={changeCompany}
                                    fetchUrl="/v1/admin/companies/search"
                                    initialOptions={[]}
                                    selectedOption={companyOption}
                                    placeholder="All Companies"
                                    searchPlaceholder="Search Companies"
                                    clearable
                                />
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Department
                                </label>
                                <SearchableSelect
                                    value={departmentId}
                                    onChange={changeDepartment}
                                    fetchUrl={
                                        companyId
                                            ? `/v1/admin/departments/search?company_id=${companyId}`
                                            : "/v1/admin/departments/search"
                                    }
                                    initialOptions={[]}
                                    selectedOption={departmentOption}
                                    placeholder="All Departments"
                                    searchPlaceholder="Search Departments"
                                    clearable
                                />
                            </div>

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

            <EmployeeViewModal
                employee={viewingEmployee}
                onClose={() => setViewingEmployee(null)}
            />

            <ConfirmationModal
                show={remove.pending !== null}
                onClose={remove.cancel}
                onConfirm={remove.confirm}
                processing={remove.deleting}
                title="Delete employee"
                confirmText="Delete"
            >
                Are you sure you want to delete{" "}
                <span className="font-medium">{remove.pending?.name}</span>?
                This action cannot be undone.
            </ConfirmationModal>
        </>
    );
}

EmployeesIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
