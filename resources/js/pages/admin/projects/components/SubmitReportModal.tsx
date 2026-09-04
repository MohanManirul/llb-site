import { useEffect, useState } from 'react';
import { ChartBarSquareIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { Modal, Button, TextInput, Textarea, CalendarInput } from '@/components/ui';
import api from '@/lib/api-client';
import { flash, errorMessage, validationErrors } from '@/lib/flash';
import { formatDate as formatDay } from '@/lib/format';
import type { SalesReport } from '../types';

interface ReportFormData {
    week_start: string;
    week_end: string;
    total_sales: string;
    total_order_quantity: string;
    total_amount_spent: string;
    description: string;
}

const EMPTY: ReportFormData = {
    week_start: '',
    week_end: '',
    total_sales: '',
    total_order_quantity: '',
    total_amount_spent: '',
    description: '',
};

const WEEK_LENGTH = 7;

function weekEndFor(weekStart: string): string {
    if (!weekStart) return '';

    const [year, month, day] = weekStart.split('-').map(Number);
    if (!year || !month || !day) return '';

    return new Date(Date.UTC(year, month - 1, day + WEEK_LENGTH - 1))
        .toISOString()
        .slice(0, 10);
}

function findOverlap(weeks: SalesReport[], start: string, end: string): SalesReport | null {
    if (!start || !end) return null;

    return (
        weeks.find(
            (week) => week.week_start <= end && week.week_end >= start,
        ) ?? null
    );
}

interface SubmitReportModalProps {
    show: boolean;
    projectId: string;
    onClose?: () => void;
    onCreated?: (report: SalesReport) => void;
}

export default function SubmitReportModal({
    show,
    projectId,
    onClose,
    onCreated,
}: SubmitReportModalProps) {
    const [data, setDataState] = useState<ReportFormData>(EMPTY);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const [existingWeeks, setExistingWeeks] = useState<SalesReport[]>([]);

    useEffect(() => {
        if (show) {
            setDataState(EMPTY);
            setErrors({});
            setProcessing(false);
        }
    }, [show]);

    useEffect(() => {
        if (!show || !projectId) return undefined;

        let cancelled = false;

        api.get(`/admin/projects/${projectId}/sales-reports`)
            .then(({ data: response }) => {
                if (!cancelled) {
                    setExistingWeeks(response?.result?.data ?? []);
                }
            })
            .catch(() => {
                if (!cancelled) setExistingWeeks([]);
            });

        return () => {
            cancelled = true;
        };
    }, [show, projectId]);

    const overlap = findOverlap(existingWeeks, data.week_start, data.week_end);

    function setData(field: keyof ReportFormData, value: string) {
        setDataState((current) => ({ ...current, [field]: value }));
    }

    function setWeekStart(value: string) {
        setDataState((current) => ({
            ...current,
            week_start: value,
            week_end: weekEndFor(value),
        }));
    }

    async function submit(e: React.FormEvent) {
        e.preventDefault();

        if (overlap) return;

        setProcessing(true);
        setErrors({});

        try {
            const { data: response } = await api.post(`/admin/projects/${projectId}/sales-reports`,
                data,
            );

            flash.success('Weekly report submitted successfully.');
            onCreated?.(response.result);
            onClose?.();
        } catch (error) {
            if ((error as { response?: { status?: number } })?.response?.status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not submit the report.'));
            }
            setProcessing(false);
        }
    }

    return (
        <Modal show={show} onClose={onClose} maxWidth="lg">
            <form onSubmit={submit}>
                <div className="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div className="flex items-center gap-3">
                        <span className="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white">
                            <ChartBarSquareIcon className="h-5 w-5" />
                        </span>
                        <h3 className="text-lg font-semibold text-gray-800">
                            Submit Report
                        </h3>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Close"
                        className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-700"
                    >
                        <XMarkIcon className="h-5 w-5" />
                    </button>
                </div>

                <div className="max-h-[70vh] space-y-5 overflow-y-auto px-6 py-5">
                    <div className="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                        <CalendarInput
                            label="Week Start"
                            name="week_start"
                            value={data.week_start}
                            onChange={(e) => setWeekStart(e.target.value)}
                            error={
                                errors.week_start ||
                                (overlap
                                    ? `This week overlaps an existing report (${formatDay(overlap.week_start)} – ${formatDay(overlap.week_end)}).`
                                    : undefined)
                            }
                            required
                        />
                        <CalendarInput
                            label="Week End"
                            name="week_end"
                            value={data.week_end}
                            readOnly
                            disabled
                            error={errors.week_end}
                            required
                        />
                    </div>

                    <TextInput
                        label="Total Sales"
                        name="total_sales"
                        type="number"
                        step="1"
                        min="0"
                        placeholder="Enter total sales"
                        value={data.total_sales}
                        onChange={(e) => setData('total_sales', e.target.value)}
                        error={errors.total_sales}
                        required
                    />

                    <TextInput
                        label="Total Order Quantity"
                        name="total_order_quantity"
                        type="number"
                        step="1"
                        min="0"
                        placeholder="Enter total order"
                        value={data.total_order_quantity}
                        onChange={(e) =>
                            setData('total_order_quantity', e.target.value)
                        }
                        error={errors.total_order_quantity}
                        required
                    />

                    <TextInput
                        label="Total Amount Spent"
                        name="total_amount_spent"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="Enter amount"
                        value={data.total_amount_spent}
                        onChange={(e) =>
                            setData('total_amount_spent', e.target.value)
                        }
                        error={errors.total_amount_spent}
                        required
                    />

                    <Textarea
                        label="Description"
                        name="description"
                        rows={5}
                        placeholder="Enter a description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        error={errors.description}
                    />
                </div>

                <div className="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <Button type="submit" disabled={processing || Boolean(overlap)}>
                        {processing ? 'Submitting…' : 'Submit Report'}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
