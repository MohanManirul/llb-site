import { useCallback, useMemo, useState, type ReactNode } from 'react';
import { Link, router } from '@inertiajs/react';
import { PlusIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import { Button, ConfirmationModal, DataTable } from '@/components/ui';
import ProjectNotesModal from '../components/ProjectNotesModal';
import useDeleteResource from '@/hooks/useDeleteResource';
import usePermissions from '@/hooks/usePermissions';
import useResourceIndex from '@/hooks/useResourceIndex';
import { flash, errorMessage } from '@/lib/flash';
import { PROJECTS_URL, updateBusinessStatus } from '../api';
import buildColumns from './columns';
import { DEFAULT_HIDDEN_COLUMNS, HIDDEN_COLUMNS_KEY } from './constants';
import { SavingStatusContext } from './SavingStatusContext';
import useProjectFilters from './useProjectFilters';
import ProjectFilters from './components/ProjectFilters';
import StatusChangeModal from './components/StatusChangeModal';
import type { PendingStatus, ProjectListRow } from '../types';

export default function ProjectsIndex() {
    const { can } = usePermissions();
    const canCreateProjects = can('create projects');
    const canViewClient = can('view project client');

    const { filters, options, dispatch, activeCount, params } =
        useProjectFilters();

    const { tableProps, setPaginator, refetch } = useResourceIndex<ProjectListRow>({
        url: PROJECTS_URL,
        storageKey: 'projects',
        hiddenColumnsKey: HIDDEN_COLUMNS_KEY,
        defaultHidden: DEFAULT_HIDDEN_COLUMNS,
        filters: params,
        errorMessage: 'Could not load projects.',
    });

    const [savingStatusId, setSavingStatusId] = useState<number | null>(null);
    const [pendingStatus, setPendingStatus] = useState<PendingStatus | null>(
        null,
    );

    const [noteModalProject, setNoteModalProject] =
        useState<ProjectListRow | null>(null);

    const remove = useDeleteResource<ProjectListRow>({
        url: (project) => `${PROJECTS_URL}/${project.id}`,
        onDeleted: refetch,
        successMessage: 'Project deleted successfully.',
        errorMessage: 'Could not delete this project.',
    });

    const askStatusChange = useCallback(
        (project: ProjectListRow, value: number | string, label: string) => {
            setPendingStatus({ project, value, label });
        },
        [],
    );

    const updateStatus = useCallback(
        async (project: ProjectListRow, value: number | string) => {
            setSavingStatusId(project.id);
            try {
                const updated = await updateBusinessStatus(project.id, value);

                setPaginator((current) =>
                    current
                        ? {
                              ...current,
                              data: current.data.map((row) =>
                                  row.id === project.id
                                      ? {
                                            ...row,
                                            business_status:
                                                updated.business_status,
                                            business_status_label:
                                                updated.business_status_label,
                                        }
                                      : row,
                              ),
                          }
                        : current,
                );

                flash.success('Project status updated.');
            } catch (error) {
                flash.error(
                    errorMessage(error, 'Could not update project status.'),
                );
            } finally {
                setSavingStatusId(null);
                setPendingStatus(null);
            }
        },
        [setPaginator],
    );

    const columns = useMemo(
        () =>
            buildColumns({
                canViewClient,
                onRequestStatusChange: askStatusChange,
                onOpenNotes: setNoteModalProject,
                onDelete: remove.request,
            }),
        [canViewClient, askStatusChange, remove.request],
    );

    return (
        <>
            <PageHeader
                title="Projects"
                action={
                    canCreateProjects ? (
                        <Link href="/admin/projects/create">
                            <Button size="sm">
                                <PlusIcon className="h-4 w-4" />
                                Create
                            </Button>
                        </Link>
                    ) : undefined
                }
            />

            <div className="flex flex-col">
                <SavingStatusContext.Provider value={savingStatusId}>
                    <DataTable
                        columns={columns}
                        {...tableProps}
                        onRowClick={(row: ProjectListRow) =>
                            router.visit(`/admin/projects/${row.id}`)
                        }
                        filters={
                            <ProjectFilters
                                filters={filters}
                                options={options}
                                dispatch={dispatch}
                                activeCount={activeCount}
                            />
                        }
                    />
                </SavingStatusContext.Provider>
            </div>

            <StatusChangeModal
                pending={pendingStatus}
                saving={savingStatusId !== null}
                onClose={() => setPendingStatus(null)}
                onConfirm={() =>
                    updateStatus(pendingStatus!.project, pendingStatus!.value)
                }
            />

            <ProjectNotesModal
                show={noteModalProject !== null}
                project={noteModalProject}
                onClose={() => setNoteModalProject(null)}
                canAdd={noteModalProject?.can_add_notes === true}
            />

            <ConfirmationModal
                show={remove.pending !== null}
                onClose={remove.cancel}
                onConfirm={remove.confirm}
                processing={remove.deleting}
                title="Delete project"
                confirmText="Delete"
            >
                Are you sure you want to delete{' '}
                <span className="font-medium">
                    {remove.pending?.project_name}
                </span>
                ? Its notes, sales reports, payments and invoices go with it,
                and this cannot be undone.
            </ConfirmationModal>
        </>
    );
}

ProjectsIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
