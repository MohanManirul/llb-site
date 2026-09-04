import { useEffect, useRef, useState } from "react";
import {
    UsersIcon,
    ChevronDownIcon,
    MagnifyingGlassIcon,
} from "@heroicons/react/24/outline";
import type { TeamMemberOption } from "../../types";

interface MembersCellProps {
    members: TeamMemberOption[];
    count: number;
    selectedMember: TeamMemberOption | null;
    onSelectMember: (option: TeamMemberOption) => void;
}

export default function MembersCell({ members, count, selectedMember, onSelectMember }: MembersCellProps) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState("");
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (ref.current && !ref.current.contains(e.target as Node)) {
                setOpen(false);
                setQuery("");
            }
        }
        document.addEventListener("mousedown", handleClickOutside);
        return () =>
            document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    const normalized = query.trim().toLowerCase();
    const filtered = normalized
        ? members.filter((m) =>
              String(m.label).toLowerCase().includes(normalized),
          )
        : members;

    const selectedInRow =
        selectedMember &&
        members.some(
            (m) => String(m.value) === String(selectedMember.value),
        )
            ? selectedMember.label
            : null;

    return (
        <div ref={ref} className="relative inline-flex items-center gap-2">
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                disabled={count === 0}
                className="inline-flex items-center gap-1 rounded text-gray-700 hover:text-gray-900 disabled:cursor-default disabled:hover:text-gray-700"
            >
                <UsersIcon className="h-4 w-4 text-gray-400" />
                {count ?? 0}
                {count > 0 && (
                    <ChevronDownIcon className="h-3 w-3 text-gray-400" />
                )}
            </button>

            {selectedInRow && (
                <span
                    className="max-w-[10rem] truncate text-sm font-medium text-indigo-600"
                    title={selectedInRow}
                >
                    {selectedInRow}
                </span>
            )}

            {open && count > 0 && (
                <div className="absolute left-0 top-full z-20 mt-1 w-56 rounded-md border border-gray-200 bg-white shadow-lg">
                    <div className="p-2">
                        <div className="relative">
                            <input
                                autoFocus
                                type="text"
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Search members"
                                className="w-full rounded-md border border-gray-300 px-3 py-2 pr-9 text-sm text-gray-700 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />
                            <MagnifyingGlassIcon className="absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        </div>
                    </div>

                    <ul className="max-h-56 overflow-y-auto px-1 pb-1">
                        {filtered.length === 0 && (
                            <li className="px-3 py-2 text-sm text-gray-400">
                                No members found
                            </li>
                        )}
                        {filtered.map((m) => (
                            <li key={m.value}>
                                <button
                                    type="button"
                                    onClick={() => {
                                        setOpen(false);
                                        setQuery("");
                                        onSelectMember(m);
                                    }}
                                    className="w-full truncate rounded px-3 py-2 text-left text-sm text-gray-700 transition hover:bg-gray-50"
                                >
                                    {m.label}
                                </button>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}
