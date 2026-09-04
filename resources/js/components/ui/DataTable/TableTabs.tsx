export interface TableTab {
    value: string;
    label: string;
}

export interface TableTabsProps {
    tabs: TableTab[];
    activeTab?: string;
    onTabChange?: (value: string) => void;
    className?: string;
}

export default function TableTabs({
    tabs,
    activeTab,
    onTabChange,
    className = '',
}: TableTabsProps) {
    if (tabs.length === 0) return null;

    return (
        <div className={'flex flex-wrap items-center gap-1 ' + className}>
            {tabs.map((tab) => (
                <button
                    key={tab.value}
                    type="button"
                    aria-pressed={activeTab === tab.value}
                    onClick={() => onTabChange?.(tab.value)}
                    className={
                        'whitespace-nowrap rounded-lg px-3 py-1 text-xs font-medium capitalize transition ' +
                        'focus:outline-none focus:ring-2 focus:ring-brand-accent/30 ' +
                        (activeTab === tab.value
                            ? 'bg-gray-200 text-gray-900'
                            : 'text-gray-500 hover:bg-gray-100')
                    }
                >
                    {tab.label}
                </button>
            ))}
        </div>
    );
}
