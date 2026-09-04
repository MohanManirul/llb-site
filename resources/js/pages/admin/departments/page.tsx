import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from "react";
import { PlusIcon, PencilSquareIcon } from "@heroicons/react/24/outline";

import DashboardLayout from '@/components/common/DashboardLayout';
import AdminPageLayout from "@/components/admin/AdminPageLayout";
import {
    Button,
    ConfirmationModal,
    DataTable,
    DeleteButton,
    SearchableSelect,
    TableFilters,
    TableSelect,
} from "@/components/ui";
import DepartmentFormModal from "./components/DepartmentFormModal";
import useDeleteResource from "@/hooks/useDeleteResource";
import usePermissions from "@/hooks/usePermissions";
import useStoredState from "@/hooks/useStoredState";
import api from "@/lib/api-client";
import { dataTablePagination, type ApiEnvelope, type SimpleResourcePaginator } from "@/lib/api-types";
import { flash, errorMessage } from "@/lib/flash";
import { formatDate } from "@/lib/format";
import type { Department } from "./types";

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: Department) => ReactNode;
}

interface CompanyOption {
    value: number | string;
    label: string;
    image?: string | null;
}

export default function DepartmentsIndex() {
    const { can } = usePermissions();
    const canDelete = can("delete departments");

    const [search, setSearch] = useState("");

    const [isActive, setIsActive] = useState("");

    const [companyId, setCompanyId] = useState<number | string>("");

    const [selectedCompany, setSelectedCompany] = useState<CompanyOption | null>(null);

    const [sortColumn, setSortColumn] = useState("");

    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>("desc");

    const [page, setPage] = useState(1);

    const [perPage, setPerPage] = useStoredState(
        "datatable:departments:per_page",
        10,
    );
    const [hiddenColumns, setHiddenColumns] = useStoredState<string[]>(
        "datatable:departments:hidden_columns",
        [],
    );

    const [paginator, setPaginator] = useState<SimpleResourcePaginator<Department> | null>(null);

    const [loading, setLoading] = useState(true);

    const [formOpen, setFormOpen] = useState(false);
    const [formDepartment, setFormDepartment] = useState<Department | null>(null);
    const [formKey, setFormKey] = useState(0);

    const openForm = useCallback((department: Department | null) => {
        setFormDepartment(department);
        setFormKey((key) => key + 1);
        setFormOpen(true);
    }, []);

    const remove = useDeleteResource<Department>({
        url: (department) => `/admin/departments/${department.id}`,
        onDeleted: () => fetchDepartments(),
        successMessage: "Department deleted successfully.",
        errorMessage: "Could not delete this department.",
    });

    const columns = useMemo<Column[]>(
        () => [
            {
                key: "company_name",
                header: "Company",
                render: (row) => row.company_name ?? "—",
                sortable: true,
            },

            {
                key: "name",
                header: "Department",
                sortable: true,
            },

            {
                key: "description",
                header: "Description",
                render: (row) =>
                    row.description
                        ? `${row.description.substring(0, 50)}${row.description.length > 50 ? "..." : ""}`
                        : "—",
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
                        <button
                            type="button"
                            onClick={() => openForm(row)}
                            className="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800"
                        >
                            <PencilSquareIcon className="h-4 w-4" />
                            Edit
                        </button>

                        {canDelete && (
                            <DeleteButton onDelete={() => remove.request(row)} />
                        )}
                    </div>
                ),
            },
        ],
        [openForm, canDelete, remove.request],
    );

    const [debouncedSearch, setDebouncedSearch] = useState("");
    useEffect(() => {
        const timer = setTimeout(() => setDebouncedSearch(search), 300);
        return () => clearTimeout(timer);
    }, [search]);

    const isFirstRender = useRef(true);
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        setPage(1);
    }, [
        debouncedSearch,
        isActive,
        companyId,
        perPage,
        sortColumn,
        sortDirection,
    ]);

    const fetchDepartments = useCallback(async () => {
        setLoading(true);
        try {
            const { data } = await api.get<ApiEnvelope<SimpleResourcePaginator<Department>>>("/admin/departments", {
                params: {
                    search: debouncedSearch,
                    is_active: isActive,
                    company_id: companyId,
                    per_page: perPage,
                    sort: sortColumn,
                    direction: sortDirection,
                    page,
                },
            });
            setPaginator(data.result);
        } catch (error) {
            flash.error(errorMessage(error, "Could not load departments."));
        } finally {
            setLoading(false);
        }
    }, [
        debouncedSearch,
        isActive,
        companyId,
        perPage,
        sortColumn,
        sortDirection,
        page,
    ]);

    useEffect(() => {
        fetchDepartments();
    }, [fetchDepartments]);

    function changeSort(column: string) {
        const direction =
            sortColumn === column && sortDirection === "asc" ? "desc" : "asc";

        setSortColumn(column);
        setSortDirection(direction);
    }

    function changeCompany(value: number | string, option?: CompanyOption | null) {
        setCompanyId(value);
        setSelectedCompany(value ? (option ?? null) : null);
    }

    return (
        <AdminPageLayout
            action={
                <Button size="sm" onClick={() => openForm(null)}>
                    <PlusIcon className="h-4 w-4" />
                    Create
                </Button>
            }
        >
            <div className="flex flex-col p-4">
                <DataTable
                    columns={columns}
                    rows={paginator?.data ?? []}
                    pagination={dataTablePagination(paginator)}
                    onPageChange={setPage}
                    loading={loading}
                    search={search}
                    onSearchChange={setSearch}
                    perPage={perPage}
                    onPerPageChange={setPerPage}
                    hiddenColumns={hiddenColumns}
                    onHiddenColumnsChange={setHiddenColumns}
                    sort={{
                        column: sortColumn,
                        direction: sortDirection,
                    }}
                    onSort={changeSort}
                    filters={
                        <TableFilters
                            activeCount={
                                (isActive ? 1 : 0) + (companyId ? 1 : 0)
                            }
                            onClear={() => {
                                setIsActive("");
                                setCompanyId("");
                                setSelectedCompany(null);
                            }}
                        >
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Company
                                </label>

                                <SearchableSelect
                                    value={companyId}
                                    onChange={changeCompany}
                                    fetchUrl="/v1/admin/companies/search"
                                    selectedOption={selectedCompany}
                                    placeholder="All Companies"
                                    searchPlaceholder="Search companies"
                                />
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Status
                                </label>

                                <TableSelect
                                    value={isActive}
                                    onChange={(e) => setIsActive(e.target.value)}
                                >
                                    <option value="">All Status</option>

                                    <option value="active">Active</option>

                                    <option value="inactive">Inactive</option>
                                </TableSelect>
                            </div>
                        </TableFilters>
                    }
                />
            </div>

            <DepartmentFormModal
                key={formKey}
                show={formOpen}
                department={formDepartment}
                onClose={() => setFormOpen(false)}
                onSaved={() => {
                    setFormOpen(false);
                    fetchDepartments();
                }}
            />

            <ConfirmationModal
                show={remove.pending !== null}
                onClose={remove.cancel}
                onConfirm={remove.confirm}
                processing={remove.deleting}
                title="Delete department"
                confirmText="Delete"
            >
                Are you sure you want to delete{" "}
                <span className="font-medium">{remove.pending?.name}</span>?
                This action cannot be undone.
            </ConfirmationModal>
        </AdminPageLayout>
    );
}

DepartmentsIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
