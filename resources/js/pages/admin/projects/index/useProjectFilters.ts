import { useMemo, useReducer, type Dispatch } from 'react';
import type {
    PickerOption,
    ProjectFilterOptions,
    ProjectFilterState,
    ProjectFilters,
} from '../types';

export type ProjectFilterAction =
    | { type: 'setCompany'; value: string; option: PickerOption | null }
    | { type: 'setDepartment'; value: string; option: PickerOption | null }
    | { type: 'setTeam'; value: string; option: PickerOption | null }
    | { type: 'setHealth'; value: string }
    | { type: 'setBusinessStatus'; value: string }
    | { type: 'clear' };

const INITIAL_STATE: ProjectFilterState = {
    filters: {
        companyId: '',
        departmentId: '',
        teamId: '',
        healthStatus: '',
        businessStatusId: '',
    },
    options: { company: null, department: null, team: null },
};

function reducer(
    state: ProjectFilterState,
    action: ProjectFilterAction,
): ProjectFilterState {
    switch (action.type) {
        case 'setCompany':
            return {
                filters: {
                    ...state.filters,
                    companyId: action.value,
                    departmentId: '',
                    teamId: '',
                },
                options: {
                    company: action.option,
                    department: null,
                    team: null,
                },
            };

        case 'setDepartment':
            return {
                filters: {
                    ...state.filters,
                    departmentId: action.value,
                    teamId: '',
                },
                options: {
                    ...state.options,
                    department: action.option,
                    team: null,
                },
            };

        case 'setTeam':
            return {
                filters: { ...state.filters, teamId: action.value },
                options: { ...state.options, team: action.option },
            };

        case 'setHealth':
            return {
                ...state,
                filters: { ...state.filters, healthStatus: action.value },
            };

        case 'setBusinessStatus':
            return {
                ...state,
                filters: { ...state.filters, businessStatusId: action.value },
            };

        case 'clear':
            return INITIAL_STATE;

        default:
            return state;
    }
}

export interface UseProjectFiltersResult {
    filters: ProjectFilters;
    options: ProjectFilterOptions;
    dispatch: Dispatch<ProjectFilterAction>;
    activeCount: number;
    params: Record<string, string>;
}

export default function useProjectFilters(): UseProjectFiltersResult {
    const [state, dispatch] = useReducer(reducer, INITIAL_STATE);
    const { filters, options } = state;

    const performance = useMemo(() => {
        if (typeof window === 'undefined') return undefined;

        const value = new URLSearchParams(window.location.search).get(
            'performance',
        );

        return value === 'risk' || value === 'performing' ? value : undefined;
    }, []);

    const activeCount = useMemo(
        () =>
            (filters.companyId ? 1 : 0) +
            (filters.departmentId ? 1 : 0) +
            (filters.teamId ? 1 : 0) +
            (filters.healthStatus ? 1 : 0) +
            (filters.businessStatusId ? 1 : 0),
        [filters],
    );

    const params = useMemo(
        () => ({
            company_id: filters.companyId,
            department_id: filters.departmentId,
            team_id: filters.teamId,
            health_status: filters.healthStatus,
            business_status: filters.businessStatusId,
            performance,
        }),
        [filters, performance],
    );

    return { filters, options, dispatch, activeCount, params };
}
