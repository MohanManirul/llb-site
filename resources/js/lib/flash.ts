import type { AxiosError } from 'axios';

export type FlashType = 'success' | 'error';

export interface FlashToast {
    type: FlashType;
    message: string;
}

type FlashListener = (toast: FlashToast) => void;

const listeners = new Set<FlashListener>();

function emit(type: FlashType, message?: string | null) {
    if (!message) return;
    listeners.forEach((listener) => listener({ type, message }));
}

export const flash = {
    success: (message?: string | null) => emit('success', message),
    error: (message?: string | null) => emit('error', message),

    subscribe(listener: FlashListener): () => void {
        listeners.add(listener);
        return () => {
            listeners.delete(listener);
        };
    },
};

interface ValidationErrorBody {
    message?: string;
    errors?: Record<string, string | string[]>;
}

export function errorMessage(error: AxiosError<ValidationErrorBody> | unknown, fallback = 'Something went wrong.'): string {
    const data = (error as AxiosError<ValidationErrorBody>)?.response?.data;
    if (!data) return (error as Error)?.message || fallback;

    if (data.message) return data.message;

    const first = data.errors && Object.values(data.errors)[0];
    return (Array.isArray(first) ? first[0] : first) || fallback;
}

export function validationErrors(error: AxiosError<ValidationErrorBody> | unknown): Record<string, string> {
    const bag = (error as AxiosError<ValidationErrorBody>)?.response?.data?.errors;
    if (!bag) return {};

    return Object.fromEntries(
        Object.entries(bag).map(([field, messages]) => [
            field,
            Array.isArray(messages) ? messages[0] : messages,
        ]),
    );
}
