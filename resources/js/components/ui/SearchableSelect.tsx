import {
    KeyboardEvent as ReactKeyboardEvent,
    ReactNode,
    useEffect,
    useRef,
    useState,
} from 'react';
import {
    ChevronUpDownIcon,
    MagnifyingGlassIcon,
    CheckIcon,
    XMarkIcon,
} from '@heroicons/react/24/outline';
import Field, { useFieldIds } from './Field';
import { ControlSize, controlClasses } from './tokens';

export interface SearchableSelectOption {
    value: string | number;
    label: string;
    image_url?: string | null;
    thumbnail_url?: string | null;
    description?: string;
    isAll?: boolean;
    custom?: boolean;
}

interface OptionPage {
    options: SearchableSelectOption[];
    nextUrl: string | null;
}

const EMPTY_PAGE: OptionPage = { options: [], nextUrl: null };

function asOptions(value: unknown): SearchableSelectOption[] | null {
    return Array.isArray(value) ? (value as SearchableSelectOption[]) : null;
}

function parsePayload(payload: unknown): OptionPage {
    const direct = asOptions(payload);
    if (direct) return { options: direct, nextUrl: null };

    const body = (payload ?? {}) as Record<string, unknown>;

    for (const key of ['result', 'options', 'designations', 'data']) {
        const candidate = body[key];

        const list = asOptions(candidate);
        if (list) return { options: list, nextUrl: null };

        if (candidate && typeof candidate === 'object') {
            const page = candidate as Record<string, unknown>;
            const rows = asOptions(page.data);

            if (rows) {
                const links = (page.links ?? {}) as Record<string, unknown>;

                return {
                    options: rows,
                    nextUrl: typeof links.next === 'string' ? links.next : null,
                };
            }
        }
    }

    return EMPTY_PAGE;
}

async function fetchPage(url: string, signal: AbortSignal): Promise<OptionPage> {
    const res = await fetch(url, {
        signal,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!res.ok) {
        throw new Error(`Request failed with status ${res.status}`);
    }

    return parsePayload(await res.json().catch(() => null));
}

function resolveImageUrl(option?: SearchableSelectOption | null): string | null {
    const src = option?.thumbnail_url ?? option?.image_url;

    return src ? src : null;
}

function initialsFor(label: string): string {
    return label
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase() ?? '')
        .join('');
}

function OptionAvatar({ option, isSelected }: { option: SearchableSelectOption; isSelected: boolean }) {
    const src = resolveImageUrl(option);

    return (
        <span
            className={`mr-3 flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full text-[11px] font-semibold ${
                isSelected ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'
            }`}
        >
            {src ? (
                <img src={src} alt="" className="h-full w-full object-cover" />
            ) : (
                initialsFor(option.label)
            )}
        </span>
    );
}

export interface SearchableSelectProps {
    label?: ReactNode;
    name?: string;
    id?: string;
    value?: string | number | null;
    onChange: (value: string | number, option?: SearchableSelectOption | null) => void;
    fetchUrl?: string | null;
    initialOptions?: SearchableSelectOption[];
    selectedOption?: SearchableSelectOption | null;
    placeholder?: string;
    searchPlaceholder?: string;
    error?: string;
    hint?: ReactNode;
    required?: boolean;
    disabled?: boolean;
    size?: ControlSize;
    debounceMs?: number;
    clearable?: boolean;
    allOptionLabel?: string | null;
    allowCustom?: boolean;
    onCustomValueChange?: (value: string) => void;
    customOptionLabel?: (search: string) => string;
    excludeValues?: (string | number)[];
}

export default function SearchableSelect({
    label,
    name,
    id,
    value,
    onChange,
    fetchUrl,
    initialOptions = [],
    selectedOption = null,
    placeholder = 'Select an option',
    searchPlaceholder = 'Search here',
    error,
    hint,
    required = false,
    disabled = false,
    size = 'md',
    debounceMs = 300,
    clearable = false,
    allOptionLabel = null,
    allowCustom = false,
    onCustomValueChange,
    customOptionLabel = (search) => `Use: ${search}`,
    excludeValues = [],
}: SearchableSelectProps) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [options, setOptions] = useState<SearchableSelectOption[]>(initialOptions);
    const [nextUrl, setNextUrl] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const [loadingMore, setLoadingMore] = useState(false);
    const [selected, setSelected] = useState<SearchableSelectOption | null>(selectedOption);
    const [activeIndex, setActiveIndex] = useState(0);

    const { fieldId, hintId, errorId, describedBy } = useFieldIds({
        id,
        name,
        hint,
        error,
    });
    const listboxId = `${fieldId}-listbox`;
    const optionDomId = (index: number) => `${fieldId}-option-${index}`;

    useEffect(() => {
        if (!selectedOption) {
            if (selected) setSelected(null);
            return;
        }

        if (String(selectedOption.value) !== String(selected?.value)) {
            setSelected(selectedOption);
        }
    }, [selectedOption]);

    useEffect(() => {
        if (initialOptions.length > 0) return;
        setOptions([]);
    }, [fetchUrl]);

    const wrapperRef = useRef<HTMLDivElement>(null);
    const triggerRef = useRef<HTMLButtonElement>(null);
    const searchRef = useRef<HTMLInputElement>(null);
    const listRef = useRef<HTMLUListElement>(null);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const abortRef = useRef<AbortController | null>(null);
    const moreAbortRef = useRef<AbortController | null>(null);

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (wrapperRef.current && !wrapperRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () =>
            document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    useEffect(() => {
        return () => {
            abortRef.current?.abort();
            moreAbortRef.current?.abort();
            if (debounceRef.current) clearTimeout(debounceRef.current);
        };
    }, []);

    useEffect(() => {
        if (!open) return;

        setSearch('');
        setActiveIndex(0);
        const frame = requestAnimationFrame(() => searchRef.current?.focus());

        setNextUrl(null);

        if (initialOptions.length > 0) {
            setOptions(initialOptions);
            return () => cancelAnimationFrame(frame);
        }

        if (fetchUrl) {
            abortRef.current?.abort();
            abortRef.current = new AbortController();
            const controller = abortRef.current;

            (async () => {
                setLoading(true);
                try {
                    const page = await fetchPage(fetchUrl, controller.signal);
                    setOptions(page.options);
                    setNextUrl(page.nextUrl);
                } catch (err) {
                    if ((err as Error).name !== 'AbortError') {
                        setOptions([]);
                    }
                } finally {
                    if (!controller.signal.aborted) setLoading(false);
                }
            })();
        }

        return () => cancelAnimationFrame(frame);
    }, [open]);

    useEffect(() => {
        if (!open) return;

        if (!search.trim()) {
            if (initialOptions.length > 0) setOptions(initialOptions);
            setLoading(false);
            return;
        }

        if (!fetchUrl) {
            setOptions([]);
            setNextUrl(null);
            setLoading(false);
            return;
        }

        if (debounceRef.current) clearTimeout(debounceRef.current);
        setLoading(true);

        debounceRef.current = setTimeout(async () => {
            abortRef.current?.abort();
            abortRef.current = new AbortController();
            const controller = abortRef.current;

            try {
                const separator = fetchUrl?.includes('?') ? '&' : '?';
                const page = await fetchPage(
                    `${fetchUrl}${separator}search=${encodeURIComponent(search)}`,
                    controller.signal,
                );

                setOptions(page.options);
                setNextUrl(page.nextUrl);
                setLoading(false);
            } catch (err) {
                if ((err as Error).name !== 'AbortError') {
                    setOptions([]);
                    setNextUrl(null);
                    setLoading(false);
                }
            }
        }, debounceMs);

        return () => {
            if (debounceRef.current) clearTimeout(debounceRef.current);
        };
    }, [search, open]);

    async function loadMore() {
        if (!nextUrl || loadingMore) return;

        moreAbortRef.current = new AbortController();
        const controller = moreAbortRef.current;
        const url = nextUrl;

        setLoadingMore(true);

        try {
            const page = await fetchPage(url, controller.signal);

            setOptions((current) => [...current, ...page.options]);
            setNextUrl(page.nextUrl);
        } catch (err) {
            if ((err as Error).name !== 'AbortError') setNextUrl(null);
        } finally {
            if (!controller.signal.aborted) setLoadingMore(false);
        }
    }

    function onListScroll(event: React.UIEvent<HTMLUListElement>) {
        const list = event.currentTarget;
        const remaining = list.scrollHeight - list.scrollTop - list.clientHeight;

        if (remaining < 48) void loadMore();
    }

    function clearSelection() {
        setSelected(null);
        onChange('', null);
    }

    function select(opt: SearchableSelectOption) {
        if (opt.isAll) {
            setSelected(null);
            onChange('', null);
            closeAndRefocus();
            return;
        }

        if (opt.custom && onCustomValueChange) {
            const customValue = opt.value as string;
            const customOption: SearchableSelectOption = { value: customValue, label: customValue };
            setSelected(customOption);
            onCustomValueChange(customValue);
            onChange(customValue);
            closeAndRefocus();
            return;
        }

        setSelected(opt);
        onChange(opt.value, opt);
        closeAndRefocus();
    }

    function closeAndRefocus() {
        setOpen(false);
        triggerRef.current?.focus();
    }

    const excludeSet = new Set((excludeValues ?? []).map(String));
    const availableOptions = options.filter(
        (opt) =>
            String(opt.value) === String(value) ||
            !excludeSet.has(String(opt.value)),
    );

    const trimmedSearch = search.trim();
    const normalizedSearch = trimmedSearch.toLowerCase();
    const hasCustomOption =
        allowCustom &&
        normalizedSearch &&
        !availableOptions.some(
            (opt) => String(opt.label).toLowerCase() === normalizedSearch,
        );
    const allOption: SearchableSelectOption[] =
        allOptionLabel && !trimmedSearch
            ? [{ value: '', label: allOptionLabel, isAll: true }]
            : [];

    const visibleOptions: SearchableSelectOption[] = hasCustomOption
        ? [
              {
                  value: trimmedSearch,
                  label: customOptionLabel(trimmedSearch),
                  custom: true,
              },
              ...availableOptions,
          ]
        : [...allOption, ...availableOptions];

    useEffect(() => {
        setActiveIndex((current) =>
            visibleOptions.length === 0
                ? 0
                : Math.min(current, visibleOptions.length - 1),
        );
    }, [visibleOptions.length]);

    useEffect(() => {
        if (!open || !listRef.current) return;

        const activeNode = listRef.current.querySelector<HTMLElement>(
            `[data-option-index="${activeIndex}"]`,
        );
        activeNode?.scrollIntoView({ block: 'nearest' });
    }, [activeIndex, open]);

    function moveActive(delta: number) {
        if (visibleOptions.length === 0) return;

        setActiveIndex((current) => {
            const next = current + delta;
            if (next < 0) return visibleOptions.length - 1;
            if (next >= visibleOptions.length) return 0;
            return next;
        });
    }

    function onSearchKeyDown(event: ReactKeyboardEvent<HTMLInputElement>) {
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                moveActive(1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                moveActive(-1);
                break;
            case 'Home':
                event.preventDefault();
                setActiveIndex(0);
                break;
            case 'End':
                event.preventDefault();
                setActiveIndex(Math.max(0, visibleOptions.length - 1));
                break;
            case 'Enter':
                event.preventDefault();
                if (visibleOptions[activeIndex]) select(visibleOptions[activeIndex]);
                break;
            case 'Escape':
                event.preventDefault();
                closeAndRefocus();
                break;
            case 'Tab':
                setOpen(false);
                break;
            default:
                break;
        }
    }

    function onTriggerKeyDown(event: ReactKeyboardEvent<HTMLButtonElement>) {
        if (disabled) return;

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            setOpen(true);
        }
    }

    const showClear = clearable && selected && !disabled;

    return (
        <Field
            htmlFor={fieldId}
            label={label}
            hint={hint}
            error={error}
            required={required}
            hintId={hintId}
            errorId={errorId}
            className="relative"
        >
            <div ref={wrapperRef} className="relative">
                <button
                    ref={triggerRef}
                    id={fieldId}
                    type="button"
                    role="combobox"
                    disabled={disabled}
                    onClick={() => setOpen(!open)}
                    onKeyDown={onTriggerKeyDown}
                    aria-expanded={open}
                    aria-haspopup="listbox"
                    aria-controls={open ? listboxId : undefined}
                    aria-required={required || undefined}
                    aria-invalid={error ? 'true' : undefined}
                    aria-describedby={describedBy}
                    className={`${controlClasses(size, error)} flex items-center justify-between pl-3 pr-10 text-left`}
                >
                    <span
                        className={`flex min-w-0 items-center gap-2 ${selected ? 'text-gray-800' : 'text-gray-400'}`}
                    >
                        {selected && (
                            <span className="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-[10px] font-semibold text-gray-500">
                                {resolveImageUrl(selected) ? (
                                    <img
                                        src={resolveImageUrl(selected) as string}
                                        alt=""
                                        className="h-full w-full object-cover"
                                    />
                                ) : (
                                    initialsFor(selected.label)
                                )}
                            </span>
                        )}
                        <span className="truncate">
                            {selected ? selected.label : placeholder}
                        </span>
                    </span>
                </button>

                {showClear ? (
                    <button
                        type="button"
                        onClick={clearSelection}
                        aria-label="Clear selection"
                        className="absolute inset-y-0 right-2 my-auto flex h-5 w-5 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-brand-accent/30"
                    >
                        <XMarkIcon className="h-4 w-4" />
                    </button>
                ) : (
                    <ChevronUpDownIcon className="pointer-events-none absolute inset-y-0 right-3 my-auto h-4 w-4 shrink-0 text-gray-400" />
                )}

                {open && (
                    <div className="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg">
                        <div className="p-2">
                            <div className="relative">
                                <input
                                    ref={searchRef}
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={onSearchKeyDown}
                                    placeholder={searchPlaceholder}
                                    aria-label={searchPlaceholder}
                                    aria-controls={listboxId}
                                    aria-activedescendant={
                                        visibleOptions.length > 0
                                            ? optionDomId(activeIndex)
                                            : undefined
                                    }
                                    className={
                                        'w-full rounded-lg border-2 border-brand-accent py-2.5 pl-3 pr-10 text-sm text-gray-700 placeholder-gray-400 shadow-sm ' +
                                        'focus:border-brand-accent focus:outline-none focus:ring-0'
                                    }
                                />
                                <MagnifyingGlassIcon className="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-brand-accent" />
                            </div>

                            <div
                                className={`mt-1 h-0.5 w-full overflow-hidden rounded-full ${loading ? 'bg-brand-accent/10' : 'bg-transparent'}`}
                                role="status"
                                aria-live="polite"
                                aria-label={loading ? 'Loading options' : undefined}
                            >
                                {loading && (
                                    <div className="h-full w-1/3 animate-progress rounded-full bg-brand-accent" />
                                )}
                            </div>
                        </div>

                        <ul
                            ref={listRef}
                            id={listboxId}
                            role="listbox"
                            aria-label={typeof label === 'string' ? label : undefined}
                            onScroll={onListScroll}
                            className="max-h-64 overflow-y-auto px-1 py-1"
                        >
                            {!loading && visibleOptions.length === 0 && (
                                <li className="px-4 py-3 text-sm text-gray-400">
                                    No results found
                                </li>
                            )}
                            {visibleOptions.map((opt, index) => {
                                const isSelected =
                                    String(opt.value) === String(value);
                                const isActive = index === activeIndex;

                                return (
                                    <li
                                        key={`${opt.value}-${index}`}
                                        id={optionDomId(index)}
                                        data-option-index={index}
                                        role="option"
                                        aria-selected={isSelected}
                                        onClick={() => select(opt)}
                                        onMouseEnter={() => setActiveIndex(index)}
                                        className={
                                            'mt-px mb-0.5 flex w-full cursor-pointer items-center rounded-sm px-3 py-2 text-left transition duration-150 ease-in-out ' +
                                            (isSelected
                                                ? 'bg-brand-accent font-semibold text-white'
                                                : isActive
                                                  ? 'bg-gray-100'
                                                  : 'hover:bg-gray-50')
                                        }
                                    >
                                        {!opt.isAll && (
                                            <OptionAvatar
                                                option={opt}
                                                isSelected={isSelected}
                                            />
                                        )}

                                        <span className="min-w-0 flex-1">
                                            <span
                                                className={`block truncate text-sm font-medium ${isSelected ? 'text-white' : 'text-gray-800'}`}
                                            >
                                                {opt.label}
                                            </span>
                                            {opt.description && (
                                                <span
                                                    className={`block truncate text-sm ${isSelected ? 'text-white/80' : 'text-gray-400'}`}
                                                >
                                                    {opt.description}
                                                </span>
                                            )}
                                        </span>

                                        {isSelected && (
                                            <CheckIcon className="ml-2 h-5 w-5 shrink-0 text-white" />
                                        )}
                                    </li>
                                );
                            })}

                            {loadingMore && (
                                <li className="px-4 py-2 text-sm text-gray-400">Loading more…</li>
                            )}
                        </ul>
                    </div>
                )}
            </div>
        </Field>
    );
}
