import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import type { ProjectListRow } from './types';

export const PROJECTS_URL = '/admin/projects';

export async function updateBusinessStatus(
    id: number,
    value: number | string,
): Promise<ProjectListRow> {
    const { data } = await api.patch<ApiEnvelope<ProjectListRow>>(
        `${PROJECTS_URL}/${id}/business-status`,
        { business_status: value },
    );

    return data.result;
}
