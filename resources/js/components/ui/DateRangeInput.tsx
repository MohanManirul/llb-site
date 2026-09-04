import { ReactNode, useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { CalendarDaysIcon } from '@heroicons/react/24/outline';
import { DateRangePicker, createStaticRanges } from 'react-date-range';
import { endOfDay, format, parseISO, startOfDay, subDays } from 'date-fns';
import Field, { useFieldIds } from './Field';
import 'react-date-range/dist/styles.css';
import 'react-date-range/dist/theme/default.css';

const NARROW_BREAKPOINT = 768;
const VIEWPORT_MARGIN = 8;

const RANGE_COLOR = '#6366f1';

interface DateRange {
    startDate: Date;
    endDate: Date;
}

interface StaticRangeLike {
    label?: string;
    isSelected: (range: DateRange) => boolean;
}

const ALL_TIME_START = new Date(0);

const STATIC_RANGES = createStaticRanges([
    {
        label: 'Today',
        range: () => ({ startDate: startOfDay(new Date()), endDate: endOfDay(new Date()) }),
    },
    {
        label: 'Yesterday',
        range: () => ({
            startDate: startOfDay(subDays(new Date(), 1)),
            endDate: endOfDay(subDays(new Date(), 1)),
        }),
    },
    {
        label: 'Last 7 days',
        range: () => ({
            startDate: startOfDay(subDays(new Date(), 6)),
            endDate: endOfDay(new Date()),
        }),
    },
    {
        label: 'Last 30 days',
        range: () => ({
            startDate: startOfDay(subDays(new Date(), 29)),
            endDate: endOfDay(new Date()),
        }),
    },
    {
        label: 'Last 90 days',
        range: () => ({
            startDate: startOfDay(subDays(new Date(), 89)),
            endDate: endOfDay(new Date()),
        }),
    },
    {
        label: 'Last 365 days',
        range: () => ({
            startDate: startOfDay(subDays(new Date(), 364)),
            endDate: endOfDay(new Date()),
        }),
    },
    {
        label: 'Maximum',
        range: () => ({ startDate: ALL_TIME_START, endDate: endOfDay(new Date()) }),
        isSelected: () => false,
    },
]);

const STATIC_RANGES_WITH_CUSTOM = [
    ...STATIC_RANGES,
    {
        label: 'Custom',
        range: (props: { ranges?: DateRange[] }) =>
            props.ranges?.[0] ?? { startDate: new Date(), endDate: new Date() },
        isSelected: (range: DateRange) =>
            !STATIC_RANGES.some((preset: StaticRangeLike) => preset.isSelected(range)),
    },
];

function toDate(value?: string | null) {
    return value ? parseISO(value) : new Date();
}

function presetLabelFor(from?: string | null, to?: string | null) {
    if (!from || !to) return null;

    const range = { startDate: toDate(from), endDate: toDate(to) };

    return (
        (STATIC_RANGES as StaticRangeLike[]).find((preset) =>
            preset.isSelected(range)
        )?.label ?? null
    );
}

function toIso(date: Date) {
    return format(date, 'yyyy-MM-dd');
}

function monthsForWidth(width: number) {
    return width < NARROW_BREAKPOINT ? 1 : 2;
}

export interface DateRangeInputProps {
    label?: ReactNode;
    name?: string;
    id?: string;
    from?: string | null;
    to?: string | null;
    onChange: (from: string, to: string) => void;
    placeholder?: string;
    error?: string;
    hint?: ReactNode;
    required?: boolean;
    disabled?: boolean;
    align?: 'start' | 'end';
    className?: string;
}

interface Coords {
    top: number;
    left: number;
}

interface Draft {
    from: string;
    to: string;
}

export default function DateRangeInput({
    label,
    name,
    id,
    from,
    to,
    onChange,
    placeholder = 'All dates',
    error,
    hint,
    required = false,
    disabled = false,
    align = 'start',
    className = '',
}: DateRangeInputProps) {
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState<Draft>({ from: from ?? '', to: to ?? '' });
    const [coords, setCoords] = useState<Coords | null>(null);
    const [months, setMonths] = useState(() =>
        monthsForWidth(typeof window === 'undefined' ? 1024 : window.innerWidth),
    );
    const buttonRef = useRef<HTMLButtonElement>(null);
    const popoverRef = useRef<HTMLDivElement>(null);

    const { fieldId, hintId, errorId, describedBy } = useFieldIds({
        id,
        name,
        hint,
        error,
    });

    function openPopover() {
        setDraft({ from: from ?? '', to: to ?? '' });
        setMonths(monthsForWidth(window.innerWidth));
        setOpen(true);
    }

    useLayoutEffect(() => {
        if (!open || !buttonRef.current || !popoverRef.current) return;

        const buttonRect = buttonRef.current.getBoundingClientRect();
        const popoverRect = popoverRef.current.getBoundingClientRect();

        let left =
            align === 'end'
                ? buttonRect.right - popoverRect.width
                : buttonRect.left;

        if (left + popoverRect.width > window.innerWidth - VIEWPORT_MARGIN) {
            left = buttonRect.right - popoverRect.width;
        }
        left = Math.min(left, window.innerWidth - popoverRect.width - VIEWPORT_MARGIN);
        left = Math.max(VIEWPORT_MARGIN, left);

        let top = buttonRect.bottom + VIEWPORT_MARGIN;
        if (top + popoverRect.height > window.innerHeight - VIEWPORT_MARGIN) {
            top = Math.max(VIEWPORT_MARGIN, buttonRect.top - popoverRect.height - VIEWPORT_MARGIN);
        }

        setCoords({ top, left });
    }, [open, months, align]);

    useEffect(() => {
        if (!open) return undefined;

        function onMouseDown(event: MouseEvent) {
            if (
                buttonRef.current?.contains(event.target as Node) ||
                popoverRef.current?.contains(event.target as Node)
            ) {
                return;
            }
            setOpen(false);
        }

        function onKeydown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setOpen(false);
                buttonRef.current?.focus();
            }
        }

        function onScroll(event: Event) {
            if (popoverRef.current?.contains(event.target as Node)) return;
            setOpen(false);
        }

        function onResize() {
            setMonths(monthsForWidth(window.innerWidth));
        }

        document.addEventListener('mousedown', onMouseDown);
        document.addEventListener('keydown', onKeydown);
        window.addEventListener('scroll', onScroll, true);
        window.addEventListener('resize', onResize);
        return () => {
            document.removeEventListener('mousedown', onMouseDown);
            document.removeEventListener('keydown', onKeydown);
            window.removeEventListener('scroll', onScroll, true);
            window.removeEventListener('resize', onResize);
        };
    }, [open]);

    const range = {
        startDate: toDate(draft.from),
        endDate: toDate(draft.to || draft.from),
        key: 'selection',
    };

    function handleSelect(item: { selection: DateRange }) {
        const { startDate, endDate } = item.selection;

        if (startDate.getTime() === ALL_TIME_START.getTime()) {
            setDraft({ from: '', to: '' });

            return;
        }

        setDraft({ from: toIso(startDate), to: toIso(endDate) });
    }

    function applyDraft() {
        onChange(draft.from, draft.to);
        setOpen(false);
    }

    function cancelDraft() {
        setDraft({ from: from ?? '', to: to ?? '' });
        setOpen(false);
    }

    const displayLabel =
        from && to
            ? (presetLabelFor(from, to) ??
              `${format(toDate(from), 'MMM d, yyyy')} - ${format(toDate(to), 'MMM d, yyyy')}`)
            : placeholder;

    return (
        <Field
            htmlFor={fieldId}
            label={label}
            hint={hint}
            error={error}
            required={required}
            hintId={hintId}
            errorId={errorId}
        >
            <button
                ref={buttonRef}
                id={fieldId}
                type="button"
                disabled={disabled}
                onClick={() => (open ? setOpen(false) : openPopover())}
                aria-haspopup="dialog"
                aria-expanded={open}
                aria-required={required || undefined}
                aria-invalid={error ? 'true' : undefined}
                aria-describedby={describedBy}
                className={`flex h-8.5 items-center justify-center gap-2 rounded-md border border-gray-200 bg-white px-2 text-[13px] font-semibold text-ink shadow-sm transition hover:bg-blue-50 disabled:opacity-60 ${error ? 'border-red-500' : ''} ${className}`}
            >
                <CalendarDaysIcon className="h-4 w-4 shrink-0 text-gray-600" />
                <span className="max-w-[45vw] truncate sm:max-w-none sm:whitespace-nowrap">
                    {displayLabel}
                </span>
            </button>

            {open &&
                createPortal(
                    <div
                        ref={popoverRef}
                        role="dialog"
                        aria-label={typeof label === 'string' ? label : 'Select a date range'}
                        style={{
                            position: 'fixed',
                            top: coords?.top ?? -9999,
                            left: coords?.left ?? -9999,
                            visibility: coords ? 'visible' : 'hidden',
                            zIndex: 60,
                        }}
                        className="rdr-popover max-w-[95vw] overflow-x-auto rounded-xl bg-white shadow-xl"
                    >
                        <p className="px-4 pt-4 text-[13px] text-ink-soft">
                            {draft.from && draft.to
                                ? `${format(toDate(draft.from), 'dd/MM/yyyy')} - ${format(toDate(draft.to), 'dd/MM/yyyy')}`
                                : placeholder}
                        </p>

                        <DateRangePicker
                            ranges={[range]}
                            onChange={handleSelect}
                            staticRanges={STATIC_RANGES_WITH_CUSTOM}
                            inputRanges={[]}
                            months={months}
                            direction="horizontal"
                            moveRangeOnFirstSelection={false}
                            showDateDisplay={false}
                            showMonthAndYearPickers={false}
                            weekStartsOn={1}
                            rangeColors={[RANGE_COLOR]}
                        />
                        <div className="flex justify-end gap-2 border-t border-gray-200 p-3">
                            <button
                                type="button"
                                onClick={cancelDraft}
                                className="h-[30px] rounded-chip border border-gray-200 bg-white px-3 text-[12px] font-semibold text-ink shadow-sm transition hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                onClick={applyDraft}
                                className="h-[30px] rounded-chip bg-ink px-3 text-[12px] font-semibold text-white shadow-sm transition hover:bg-black"
                            >
                                Apply
                            </button>
                        </div>
                    </div>,
                    document.body,
                )}
        </Field>
    );
}
