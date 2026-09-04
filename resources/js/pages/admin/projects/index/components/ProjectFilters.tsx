import type { Dispatch } from 'react';
import { SearchableSelect, TableFilters, TableSelect } from '@/components/ui';
import { BUSINESS_STATUS_OPTIONS } from '@/config/businessStatus';
import { HEALTH_OPTIONS } from '../constants';
import type { ProjectFilterAction } from '../useProjectFilters';
import type {
    PickerOption,
    ProjectFilterOptions,
    ProjectFilters as ProjectFilterValues,
} from '../../types';

interface ProjectFiltersProps {
    filters: ProjectFilterValues;
    options: ProjectFilterOptions;
    dispatch: Dispatch<ProjectFilterAction>;
    activeCount: number;
}

export default function ProjectFilters({
    filters,
    options,
    dispatch,
    activeCount,
}: ProjectFiltersProps) {
    const teamFilterUrl = filters.companyId
        ? `/v1/admin/projects/teams/search?company_id=${filters.companyId}` +
          (filters.departmentId
              ? `&department_id=${filters.departmentId}`
              : '')
        : null;

    function pick(
        type: 'setCompany' | 'setDepartment' | 'setTeam',
        value: string | number,
        option?: PickerOption | null,
    ) {
        dispatch({
            type,
            value: value ? String(value) : '',
            option: option ?? null,
        });
    }

    return (
        <TableFilters
            activeCount={activeCount}
            onClear={() => dispatch({ type: 'clear' })}
        >
            <div>
                <label className="mb-1 block text-xs font-medium text-gray-700">
                    Company
                </label>
                <SearchableSelect
                    value={filters.companyId}
                    onChange={(value, option) =>
                        pick('setCompany', value, option)
                    }
                    placeholder="All companies"
                    searchPlaceholder="Search companies"
                    fetchUrl="/v1/admin/projects/companies/search"
                    initialOptions={[]}
                    selectedOption={options.company}
                    allOptionLabel="All companies"
                />
            </div>
            <div>
                <label className="mb-1 block text-xs font-medium text-gray-700">
                    Department
                </label>
                <SearchableSelect
                    value={filters.departmentId}
                    onChange={(value, option) =>
                        pick('setDepartment', value, option)
                    }
                    placeholder="All departments"
                    searchPlaceholder="Search departments"
                    fetchUrl={
                        filters.companyId
                            ? `/v1/admin/projects/departments/search?company_id=${filters.companyId}`
                            : '/v1/admin/projects/departments/search'
                    }
                    initialOptions={[]}
                    selectedOption={options.department}
                    allOptionLabel="All departments"
                />
            </div>
            <div>
                <label className="mb-1 block text-xs font-medium text-gray-700">
                    Team
                </label>
                <SearchableSelect
                    value={filters.teamId}
                    onChange={(value, option) => pick('setTeam', value, option)}
                    placeholder={
                        filters.companyId
                            ? 'All teams'
                            : 'Select a company first'
                    }
                    searchPlaceholder="Search teams"
                    fetchUrl={teamFilterUrl}
                    initialOptions={[]}
                    selectedOption={options.team}
                    allOptionLabel="All teams"
                />
            </div>
            <div>
                <label className="mb-1 block text-xs font-medium text-gray-700">
                    Health
                </label>
                <TableSelect
                    value={filters.healthStatus}
                    onChange={(e: React.ChangeEvent<HTMLSelectElement>) =>
                        dispatch({ type: 'setHealth', value: e.target.value })
                    }
                >
                    <option value="">All health</option>
                    {HEALTH_OPTIONS.map((s) => (
                        <option key={s.value} value={s.value}>
                            {s.label}
                        </option>
                    ))}
                </TableSelect>
            </div>
            <div>
                <label className="mb-1 block text-xs font-medium text-gray-700">
                    Status
                </label>
                <TableSelect
                    value={filters.businessStatusId}
                    onChange={(e: React.ChangeEvent<HTMLSelectElement>) =>
                        dispatch({
                            type: 'setBusinessStatus',
                            value: e.target.value,
                        })
                    }
                >
                    <option value="">All statuses</option>
                    {BUSINESS_STATUS_OPTIONS.map((s) => (
                        <option key={s.value} value={s.value}>
                            {s.label}
                        </option>
                    ))}
                </TableSelect>
            </div>
        </TableFilters>
    );
}
