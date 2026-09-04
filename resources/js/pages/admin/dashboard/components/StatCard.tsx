import type { ComponentType, SVGProps } from 'react';

interface StatCardProps {
    label: string;
    value: string | number;
    icon: ComponentType<SVGProps<SVGSVGElement>>;
    tone: string;
}

export default function StatCard({
    label,
    value,
    icon: Icon,
    tone,
}: StatCardProps) {
    return (
        <div className="rounded-xl border border-gray-200 bg-white px-4 py-3 transition hover:shadow-sm">
            <div className="mb-2 flex items-center justify-between gap-2">
                <p
                    className="truncate border-b-2 border-dotted border-[#BFBFBF] text-[13px] font-medium text-[#4D4D4D]"
                    title={label}
                >
                    {label}
                </p>
                <Icon className={'h-4 w-4 shrink-0 ' + tone} />
            </div>

            <p className="truncate text-[15px] font-semibold text-[#313131]">
                {value}
            </p>
        </div>
    );
}
