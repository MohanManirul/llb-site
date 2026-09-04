import { useRef, useState, type ChangeEvent, type FormEvent } from "react";
import { Link, router } from "@inertiajs/react";
import { CheckIcon, PlusIcon, TrashIcon } from "@heroicons/react/24/outline";
import type { AxiosError } from "axios";
import { Button, SearchableSelect, SelectInput, Textarea, TextInput } from "@/components/ui";
import api from "@/lib/api-client";
import { flash, errorMessage, validationErrors } from "@/lib/flash";
import type { TeamDetail, TeamMemberDetail, TeamOption } from "../types";

interface MemberRow {
    key: string;
    employee_id: string | number;
    role: "leader" | "member";
    selectedOption: TeamOption | null;
}

interface TeamFormData {
    company_id: string | number;
    department_id: string | number;
    name: string;
    description: string;
    isActive: boolean;
    members: MemberRow[];
}

interface TeamFormComponentProps {
    team?: TeamDetail;
}

function memberRow(key: string, member: Partial<TeamMemberDetail> = {}): MemberRow {
    return {
        key,
        employee_id: member.employee_id ?? "",
        role: member.role ?? "member",
        selectedOption: member.employee_id
            ? {
                  value: member.employee_id,
                  label: member.name ?? "",
                  description: member.designation ?? "",
                  image_url: member.image_url ?? null,
                  thumbnail_url: member.thumbnail_url ?? null,
              }
            : null,
    };
}

export default function TeamForm({ team }: TeamFormComponentProps) {
    const isEdit = Boolean(team);

    const nextKey = useRef(0);
    function makeKey() {
        return `row-${nextKey.current++}`;
    }

    const initialMembers = (team?.members ?? []).map((m) =>
        memberRow(makeKey(), { ...m, employee_id: m.employee_id ?? m.id }),
    );

    const [data, setDataState] = useState<TeamFormData>({
        company_id: team?.company_id ?? "",
        department_id: team?.department_id ?? "",
        name: team?.name ?? "",
        description: team?.description ?? "",
        isActive: team?.is_active ?? true,
        members: initialMembers.length
            ? initialMembers
            : [memberRow(makeKey(), { role: "leader" })],
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const [selectedCompany, setSelectedCompany] = useState<TeamOption | null>(
        team?.company_id
            ? { value: team.company_id, label: team.company_name ?? "" }
            : null,
    );
    const [selectedDepartment, setSelectedDepartment] = useState<TeamOption | null>(
        team?.department_id
            ? { value: team.department_id, label: team.department_name ?? "" }
            : null,
    );

    function setData<K extends keyof TeamFormData>(field: K, value: TeamFormData[K]): void;
    function setData(updater: (current: TeamFormData) => TeamFormData): void;
    function setData(
        fieldOrUpdater: keyof TeamFormData | ((current: TeamFormData) => TeamFormData),
        value?: TeamFormData[keyof TeamFormData],
    ) {
        if (typeof fieldOrUpdater === "function") {
            setDataState(fieldOrUpdater);
            return;
        }

        setDataState((current) => ({ ...current, [fieldOrUpdater]: value }));
    }

    function setMembers(updater: (members: MemberRow[]) => MemberRow[]) {
        setDataState((current) => ({
            ...current,
            members: updater(current.members),
        }));
    }

    function addMember() {
        const hasLeader = data.members.some((m) => m.role === "leader");
        setMembers((members) => [
            ...members,
            memberRow(makeKey(), { role: hasLeader ? "member" : "leader" }),
        ]);
    }

    function removeMember(key: string) {
        setMembers((members) => members.filter((m) => m.key !== key));
    }

    function setMemberEmployee(key: string, option: TeamOption | null) {
        setMembers((members) =>
            members.map((m) =>
                m.key === key
                    ? {
                          ...m,
                          employee_id: option?.value ?? "",
                          selectedOption: option,
                      }
                    : m,
            ),
        );
    }

    function setMemberRole(key: string, role: "leader" | "member") {
        setMembers((members) =>
            members.map((m) => {
                if (m.key === key) return { ...m, role };
                if (role === "leader" && m.role === "leader")
                    return { ...m, role: "member" };
                return m;
            }),
        );
    }

    function chosenElsewhere(key: string) {
        return data.members
            .filter((m) => m.key !== key && m.employee_id !== "")
            .map((m) => String(m.employee_id));
    }

    function clearedMembers(members: MemberRow[]): MemberRow[] {
        return members.map((m) => ({
            ...m,
            employee_id: "",
            selectedOption: null,
        }));
    }

    function changeCompany(value: string, option: TeamOption | null) {
        setSelectedCompany(option ?? null);
        setSelectedDepartment(null);

        setData((prev) => ({
            ...prev,
            company_id: value,
            department_id: "",
            members: clearedMembers(prev.members),
        }));
    }

    function changeDepartment(value: string, option: TeamOption | null) {
        setSelectedDepartment(option ?? null);

        setData((prev) => ({
            ...prev,
            department_id: value,
            members: clearedMembers(prev.members),
        }));
    }

    async function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const payload = {
            company_id: data.company_id,
            department_id: data.department_id,
            name: data.name,
            description: data.description,
            is_active: data.isActive,
            members: data.members
                .filter((m) => m.employee_id !== "")
                .map((m) => ({
                    employee_id: m.employee_id,
                    role: m.role,
                })),
        };

        try {
            if (isEdit) {
                await api.put(`/admin/teams/${team!.id}`, payload);
            } else {
                await api.post("/admin/teams", payload);
            }

            flash.success(
                isEdit
                    ? "Team updated successfully."
                    : "Team created successfully.",
            );
            router.visit("/admin/teams");
        } catch (error) {
            if ((error as AxiosError)?.response?.status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, "Could not save the team."));
            }
            setProcessing(false);
        }
    }

    const canPickMembers = Boolean(data.company_id && data.department_id);

    return (
        <form onSubmit={submit} className="w-full space-y-6">
            <div className="rounded-card border border-hairline bg-white shadow-sm">
                <div className="space-y-8 p-6">
                    <section>
                        <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                            <SearchableSelect
                                label="Company"
                                required
                                value={data.company_id}
                                onChange={changeCompany}
                                error={errors.company_id}
                                placeholder="Select a Company"
                                searchPlaceholder="Search companies"
                                fetchUrl="/v1/admin/companies/search"
                                initialOptions={[]}
                                selectedOption={selectedCompany}
                            />

                            <SearchableSelect
                                label="Department"
                                required
                                value={data.department_id}
                                onChange={changeDepartment}
                                error={errors.department_id}
                                placeholder={
                                    data.company_id
                                        ? "Select a Department"
                                        : "Select a company first"
                                }
                                searchPlaceholder="Search departments"
                                fetchUrl={
                                    data.company_id
                                        ? `/v1/admin/departments/search?company_id=${data.company_id}`
                                        : "/v1/admin/departments/search"
                                }
                                initialOptions={[]}
                                selectedOption={selectedDepartment}
                            />

                            <TextInput
                                label="Team Name"
                                value={data.name}
                                onChange={(e: ChangeEvent<HTMLInputElement>) => setData("name", e.target.value)}
                                error={errors.name}
                                required
                            />

                            <SelectInput
                                label="Status"
                                value={data.isActive ? "1" : "0"}
                                onChange={(e: ChangeEvent<HTMLSelectElement>) =>
                                    setData("isActive", e.target.value === "1")
                                }
                                error={errors.is_active}
                            >
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </SelectInput>

                            <div className="md:col-span-2">
                                <Textarea
                                    label="Description"
                                    rows={3}
                                    value={data.description}
                                    onChange={(e: ChangeEvent<HTMLTextAreaElement>) =>
                                        setData("description", e.target.value)
                                    }
                                    error={errors.description}
                                />
                            </div>
                        </div>
                    </section>

                    <section>
                        <div className="mb-3 flex items-center justify-between">
                            <div>
                                <h3 className="text-sm font-semibold text-gray-900">
                                    Members
                                </h3>
                                <p className="text-xs text-gray-500">
                                    Add employees and mark exactly one as the
                                    team leader.
                                </p>
                            </div>

                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                onClick={addMember}
                                disabled={!canPickMembers}
                            >
                                <PlusIcon className="h-4 w-4" />
                                Add member
                            </Button>
                        </div>

                        {errors.members && (
                            <p className="mb-3 text-sm text-red-600">
                                {errors.members}
                            </p>
                        )}

                        {!canPickMembers && (
                            <p className="rounded-md bg-gray-50 px-3 py-4 text-center text-sm text-gray-500">
                                Select a company and department to add members.
                            </p>
                        )}

                        {canPickMembers && (
                            <div className="space-y-3">
                                {data.members.map((member, index) => {
                                    const excluded = chosenElsewhere(
                                        member.key,
                                    );

                                    return (
                                        <div
                                            key={member.key}
                                            className="flex items-start gap-3 rounded-md border border-gray-200 p-3"
                                        >
                                            <div className="flex-1">
                                                <SearchableSelect
                                                    value={member.employee_id}
                                                    onChange={(_value: string, opt: TeamOption | null) =>
                                                        setMemberEmployee(
                                                            member.key,
                                                            opt,
                                                        )
                                                    }
                                                    error={
                                                        errors[
                                                            `members.${index}.employee_id`
                                                        ]
                                                    }
                                                    placeholder="Select an employee"
                                                    searchPlaceholder="Search employees"
                                                    fetchUrl={`/v1/admin/employees/search?company_id=${data.company_id}&department_id=${data.department_id}`}
                                                    initialOptions={[]}
                                                    selectedOption={
                                                        member.selectedOption
                                                    }
                                                    excludeValues={excluded}
                                                />
                                            </div>

                                            <div className="w-40">
                                                <SelectInput
                                                    value={member.role}
                                                    onChange={(e: ChangeEvent<HTMLSelectElement>) =>
                                                        setMemberRole(
                                                            member.key,
                                                            e.target.value as "leader" | "member",
                                                        )
                                                    }
                                                    error={
                                                        errors[
                                                            `members.${index}.role`
                                                        ]
                                                    }
                                                >
                                                    <option value="leader">
                                                        Leader
                                                    </option>
                                                    <option value="member">
                                                        Member
                                                    </option>
                                                </SelectInput>
                                            </div>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    removeMember(member.key)
                                                }
                                                disabled={
                                                    data.members.length === 1
                                                }
                                                className="mt-2 shrink-0 rounded p-1 text-gray-400 transition hover:text-red-600 disabled:cursor-not-allowed disabled:hover:text-gray-400"
                                                title="Remove member"
                                            >
                                                <TrashIcon className="h-5 w-5" />
                                            </button>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </section>
                </div>
            </div>

            <div className="flex justify-end gap-3">
                <Link href="/admin/teams">
                    <Button variant="secondary" type="button">
                        Cancel
                    </Button>
                </Link>
                <Button type="submit" disabled={processing}>
                    <CheckIcon className="h-4 w-4" />
                    {isEdit ? "Save changes" : "Create Team"}
                </Button>
            </div>
        </form>
    );
}
