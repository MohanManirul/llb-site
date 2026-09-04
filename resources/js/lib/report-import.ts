const STORAGE_KEY = 'sales-report-import';
const CHANGED_EVENT = 'sales-report-import:changed';
const FINISHED_EVENT = 'sales-report-import:finished';

export interface ReportImportTicket {
    projectId: string;
    importId: string;
    startedAt: number;
}

export function readReportImport(): ReportImportTicket | null {
    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);

        if (!stored) return null;

        const ticket = JSON.parse(stored) as ReportImportTicket;

        if (!ticket?.projectId || !ticket?.importId) return null;

        return ticket;
    } catch {
        return null;
    }
}

export function startReportImport(projectId: string, importId?: string | null): void {
    if (!importId) return;

    const ticket: ReportImportTicket = {
        projectId,
        importId,
        startedAt: Date.now(),
    };

    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(ticket));
    } catch {
        //
    }

    window.dispatchEvent(new Event(CHANGED_EVENT));
}

export function clearReportImport(): void {
    try {
        window.localStorage.removeItem(STORAGE_KEY);
    } catch {
        //
    }

    window.dispatchEvent(new Event(CHANGED_EVENT));
}

export function onReportImportChange(handler: () => void): () => void {
    window.addEventListener(CHANGED_EVENT, handler);
    window.addEventListener('storage', handler);

    return () => {
        window.removeEventListener(CHANGED_EVENT, handler);
        window.removeEventListener('storage', handler);
    };
}

export function announceReportImportFinished(projectId: string): void {
    window.dispatchEvent(
        new CustomEvent(FINISHED_EVENT, { detail: { projectId } }),
    );
}

export function onReportImportFinished(
    handler: (projectId: string) => void,
): () => void {
    const listener = (event: Event) => {
        const detail = (event as CustomEvent<{ projectId?: string }>).detail;
        handler(detail?.projectId ?? '');
    };

    window.addEventListener(FINISHED_EVENT, listener);

    return () => window.removeEventListener(FINISHED_EVENT, listener);
}
