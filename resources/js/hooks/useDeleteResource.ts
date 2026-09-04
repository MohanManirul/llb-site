import { useCallback, useRef, useState } from 'react';
import api from '@/lib/api-client';
import { errorMessage as messageFromError, flash } from '@/lib/flash';

export interface UseDeleteResourceOptions<Row> {
    url: (row: Row) => string;
    onDeleted?: () => void | Promise<void>;
    successMessage?: string;
    errorMessage?: string;
}

export interface UseDeleteResourceResult<Row> {
    pending: Row | null;
    deleting: boolean;
    request: (row: Row) => void;
    cancel: () => void;
    confirm: () => Promise<void>;
}

export default function useDeleteResource<Row>({
    url,
    onDeleted,
    successMessage = 'Deleted successfully.',
    errorMessage = 'Could not delete this record.',
}: UseDeleteResourceOptions<Row>): UseDeleteResourceResult<Row> {
    const [pending, setPending] = useState<Row | null>(null);
    const [deleting, setDeleting] = useState(false);

    const optionsRef = useRef({ url, onDeleted, successMessage, errorMessage });
    optionsRef.current = { url, onDeleted, successMessage, errorMessage };

    const request = useCallback((row: Row) => setPending(row), []);
    const cancel = useCallback(() => setPending(null), []);

    const confirm = useCallback(async () => {
        if (!pending) return;

        const options = optionsRef.current;

        setDeleting(true);
        try {
            const { data } = await api.delete<{ message?: string }>(
                options.url(pending),
            );
            flash.success(data?.message ?? options.successMessage);
            setPending(null);
            await options.onDeleted?.();
        } catch (error) {
            flash.error(messageFromError(error, options.errorMessage));
        } finally {
            setDeleting(false);
        }
    }, [pending]);

    return { pending, deleting, request, cancel, confirm };
}
