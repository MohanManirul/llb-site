import { useEffect, useState, type ComponentType, type ReactNode } from "react";
import { Link, router } from "@inertiajs/react";
import {
    BriefcaseIcon,
    CheckIcon,
    CreditCardIcon,
    ExclamationTriangleIcon,
    IdentificationIcon,
    UsersIcon,
} from "@heroicons/react/24/outline";
import {
    TextInput,
    Textarea,
    SelectInput,
    CalendarInput,
    NumberStepper,
    SearchableSelect,
    Button,
    Toggle,
} from "@/components/ui";
import api from "@/lib/api-client";
import { BUSINESS_STATUS_OPTIONS } from "@/config/businessStatus";
import { flash, errorMessage, validationErrors } from "@/lib/flash";
import { formatMoney } from "@/lib/format";
import type {
    BusinessStatusOption,
    PickerOption,
    ProjectDetail,
} from "../types";

const PROJECT_TYPES = [
    { value: "regular", label: "Regular" },
    { value: "challenge_based", label: "Challenge Based" },
];

interface ProjectFormData {
    company_id: string | number;
    department_id: string | number;
    team_id: string | number;
    assigned_employee_id: string | number;
    client_id: string | number;

    project_name: string;
    business_name: string;
    website_url: string;
    description: string;

    start_date: string;
    contract_months: number | string;
    contract_days: number | string;

    contact_person: string;
    contact_email: string;
    contact_phone: string;

    package_amount: string | number;
    amount_paid: string | number;
    next_payment_date: string;

    project_type: string;
    sales_target: string | number;
    target_months: number | string;
    target_days: number | string;

    business_status: string | number;
}

interface FormSectionProps {
    icon: ComponentType<{ className?: string }>;
    tone: string;
    title: string;
    action?: ReactNode;
    children?: ReactNode;
}

function FormSection({ icon: Icon, tone, title, action, children }: FormSectionProps) {
    return (
        <section className="rounded-xl border border-gray-200 bg-white shadow-sm">
            <header className="flex items-center gap-2.5 border-b border-gray-100 px-6 py-4">
                <span
                    className={`flex h-7 w-7 items-center justify-center rounded-md ${tone}`}
                >
                    <Icon className="h-4 w-4" />
                </span>
                <h2 className="text-sm font-semibold text-gray-900">{title}</h2>
                {action && <div className="ml-auto">{action}</div>}
            </header>
            {children && (
                <div className="grid grid-cols-1 gap-x-6 gap-y-5 p-6 md:grid-cols-3">
                    {children}
                </div>
            )}
        </section>
    );
}

interface DerivedFieldProps {
    label: string;
    value?: string | null;
    placeholder?: string;
    tone: string;
    hint?: string;
}

function DerivedField({ label, value, placeholder, tone, hint }: DerivedFieldProps) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-gray-700">
                {label}
            </label>
            <div
                className={`rounded-lg border px-3 py-2 text-sm ${tone} ${
                    value ? "font-medium" : ""
                }`}
            >
                {value || placeholder}
            </div>
            {hint && <p className="mt-1 text-xs text-gray-400">{hint}</p>}
        </div>
    );
}

interface LockedFieldProps {
    label: string;
    value?: string | null;
}

function LockedField({ label, value }: LockedFieldProps) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-gray-700">
                {label}
            </label>
            <div className="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                {value ?? "—"}
                <span className="ml-2 text-xs text-gray-400">(locked)</span>
            </div>
        </div>
    );
}

interface ProjectFormProps {
    project?: ProjectDetail;
}

export default function ProjectForm({ project }: ProjectFormProps) {
    const isEdit = Boolean(project);

    const [data, setDataState] = useState<ProjectFormData>({
        company_id: project?.company_id ?? "",
        department_id: project?.department_id ?? "",
        team_id: project?.team_id ?? "",
        assigned_employee_id: project?.assigned_employee_id ?? "",
        client_id: project?.client_id ?? "",

        project_name: project?.project_name ?? "",
        business_name: project?.business_name ?? "",
        website_url: project?.website_url ?? "",
        description: project?.description ?? "",

        start_date: project?.start_date ?? "",
        contract_months: project?.contract_months ?? 12,
        contract_days: project?.contract_days ?? 0,

        contact_person: project?.contact_person ?? "",
        contact_email: project?.contact_email ?? "",
        contact_phone: project?.contact_phone ?? "",

        package_amount: project?.package_amount ?? "",
        amount_paid: project?.amount_paid ?? "",
        next_payment_date: project?.next_payment_date ?? "",

        project_type: project?.project_type ?? "regular",
        sales_target: project?.sales_target ?? "",
        target_months: project?.target_months ?? 12,
        target_days: project?.target_days ?? 0,

        business_status: project?.business_status ?? "",
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const [hasPrimaryContact, setHasPrimaryContact] = useState<boolean>(
        Boolean(
            project?.contact_person ||
                project?.contact_email ||
                project?.contact_phone,
        ),
    );

    function setData(
        field: keyof ProjectFormData | ((prev: ProjectFormData) => ProjectFormData),
        value?: unknown,
    ) {
        if (typeof field === "function") {
            setDataState(field);
            return;
        }
        setDataState((current) => ({ ...current, [field]: value }));
    }

    const isChallengeBased = data.project_type === "challenge_based";

    function changeCompany(value: string) {
        setData((prev) => ({
            ...prev,
            company_id: value,
            department_id: "",
            team_id: "",
            assigned_employee_id: "",
        }));
    }

    function changeDepartment(value: string) {
        setData((prev) => ({
            ...prev,
            department_id: value,
            team_id: "",
            assigned_employee_id: "",
        }));
    }

    function changeTeam(value: string) {
        setData((prev) => ({
            ...prev,
            team_id: value,
            assigned_employee_id: "",
        }));
    }

    async function submit(e: React.FormEvent) {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const submitted: ProjectFormData = hasPrimaryContact
            ? data
            : {
                  ...data,
                  contact_person: "",
                  contact_email: "",
                  contact_phone: "",
              };

        const payload = new FormData();
        Object.entries(submitted).forEach(([key, value]) => {
            payload.append(key, (value ?? "") as string | Blob);
        });

        if (isEdit) {
            payload.append("_method", "put");
        }

        try {
            await api.post(
                isEdit ? `/admin/projects/${project!.id}` : "/admin/projects",
                payload,
            );

            flash.success(
                isEdit
                    ? "Project updated successfully."
                    : "Project created successfully.",
            );
            router.visit("/admin/projects");
        } catch (error) {
            if ((error as { response?: { status?: number } })?.response?.status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, "Could not save the project."));
            }
            setProcessing(false);
        }
    }

    const canPickDepartment = Boolean(data.company_id);
    const canPickTeam = Boolean(data.company_id);
    const canPickOwner = Boolean(data.team_id);

    const teamFetchUrl = data.company_id
        ? `/v1/admin/projects/teams/search?company_id=${data.company_id}` +
          (data.department_id ? `&department_id=${data.department_id}` : "")
        : null;

    const addDurationFormatted = (
        dateStr: string,
        monthsInput: string | number,
        daysInput: string | number,
    ): string | null => {
        const months = parseInt(String(monthsInput), 10);
        if (!dateStr || !Number.isInteger(months) || months < 1) {
            return null;
        }
        const start = new Date(`${dateStr}T00:00:00`);
        if (Number.isNaN(start.getTime())) {
            return null;
        }
        const days = parseInt(String(daysInput), 10);
        const end = new Date(start);
        end.setMonth(end.getMonth() + months);
        end.setDate(end.getDate() + (Number.isInteger(days) ? days : 0));
        const mm = String(end.getMonth() + 1).padStart(2, "0");
        const dd = String(end.getDate()).padStart(2, "0");
        const yyyy = end.getFullYear();
        return `${mm}/${dd}/${yyyy}`;
    };

    const contractEndDate = addDurationFormatted(
        data.start_date,
        data.contract_months,
        data.contract_days,
    );

    const targetDeadline = addDurationFormatted(
        data.start_date,
        data.target_months,
        data.target_days,
    );

    const amountDue = (() => {
        const pkg = parseFloat(String(data.package_amount));
        if (Number.isNaN(pkg)) {
            return null;
        }
        const paid = parseFloat(String(data.amount_paid));
        const due = pkg - (Number.isNaN(paid) ? 0 : paid);
        return due.toFixed(2);
    })();

    const isDuePaid = amountDue !== null && parseFloat(amountDue) <= 0;

    const seededCompany: PickerOption | null = project?.company_id
        ? { value: project.company_id, label: project.company ?? "—" }
        : null;
    const seededDepartment: PickerOption | null = project?.department_id
        ? { value: project.department_id, label: project.department ?? "—" }
        : null;
    const seededTeam: PickerOption | null = project?.team_id
        ? { value: project.team_id, label: project.team ?? "—" }
        : null;
    const seededClient: PickerOption | null = project?.client_id
        ? { value: project.client_id, label: project.client?.name ?? "—" }
        : null;
    const seededEmployee: PickerOption | null = project?.assigned_employee
        ? {
              value: project.assigned_employee.id,
              label: project.assigned_employee.name,
              description: project.assigned_employee.designation,
              image_url: project.assigned_employee.image_url ?? null,
              thumbnail_url: project.assigned_employee.thumbnail_url ?? null,
          }
        : null;

    const matches = (option: PickerOption | null, value: string | number) =>
        option && String(option.value) === String(value) ? option : null;

    const clientSelected = matches(seededClient, data.client_id);
    const departmentSelected = matches(seededDepartment, data.department_id);
    const teamSelected = matches(seededTeam, data.team_id);
    const ownerSelected = matches(seededEmployee, data.assigned_employee_id);

    return (
        <form onSubmit={submit} className="w-full space-y-5">
            <FormSection
                icon={BriefcaseIcon}
                tone="bg-indigo-50 text-indigo-600"
                title="Client & Business Details"
            >
                <TextInput
                    label="Project Name"
                    value={data.project_name}
                    onChange={(e) => setData("project_name", e.target.value)}
                    error={errors.project_name}
                    placeholder="e.g. Acme Marketing 2026"
                    required
                />

                {isEdit ? (
                    <LockedField label="Client" value={seededClient?.label} />
                ) : (
                    <SearchableSelect
                        label="Client"
                        required
                        value={data.client_id}
                        onChange={(value: string) => setData("client_id", value)}
                        error={errors.client_id}
                        placeholder="Select a client"
                        searchPlaceholder="Search clients"
                        fetchUrl="/v1/admin/projects/clients/search"
                        initialOptions={[]}
                        selectedOption={clientSelected}
                    />
                )}

                <TextInput
                    label="Business Name"
                    value={data.business_name}
                    onChange={(e) => setData("business_name", e.target.value)}
                    error={errors.business_name}
                    placeholder="Business / Brand name"
                    required
                />

                <SelectInput
                    label="Business Status"
                    value={data.business_status}
                    onChange={(e) =>
                        setData("business_status", e.target.value)
                    }
                    error={errors.business_status}
                    required
                >
                    <option value="">Select a status</option>
                    {BUSINESS_STATUS_OPTIONS.map((s) => (
                        <option key={s.value} value={s.value}>
                            {s.label}
                        </option>
                    ))}
                </SelectInput>

                <TextInput
                    label="Website URL"
                    type="url"
                    value={data.website_url}
                    onChange={(e) => setData("website_url", e.target.value)}
                    error={errors.website_url}
                    placeholder="https://example.com"
                />

                <CalendarInput
                    label="Project Onboarding Date"
                    required
                    value={data.start_date}
                    onChange={(e) => setData("start_date", e.target.value)}
                    error={errors.start_date}
                />

                <NumberStepper
                    label="Contract Months"
                    value={data.contract_months}
                    onChange={(value) => setData("contract_months", value)}
                    min={1}
                    max={255}
                    error={errors.contract_months}
                    required
                />

                <NumberStepper
                    label="Days"
                    value={data.contract_days}
                    onChange={(value) => setData("contract_days", value)}
                    min={0}
                    max={365}
                    error={errors.contract_days}
                />

                <DerivedField
                    label="End Date"
                    value={contractEndDate}
                    placeholder="Auto calculated"
                    tone="border-indigo-200 bg-indigo-50 text-indigo-700"
                />

                <div className="md:col-span-3">
                    <Textarea
                        label="Description"
                        rows={3}
                        value={data.description}
                        onChange={(e) => setData("description", e.target.value)}
                        error={errors.description}
                        placeholder="Enter description"
                        required
                    />
                </div>
            </FormSection>

            <FormSection
                icon={CreditCardIcon}
                tone="bg-emerald-50 text-emerald-600"
                title="Package & Payment"
            >
                <TextInput
                    label="Package Amount"
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.package_amount}
                    onChange={(e) => setData("package_amount", e.target.value)}
                    error={errors.package_amount}
                    placeholder="0.00"
                    required
                />

                <TextInput
                    label="Amount Paid"
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.amount_paid}
                    onChange={(e) => setData("amount_paid", e.target.value)}
                    error={errors.amount_paid}
                    placeholder="0.00"
                />

                {!isDuePaid && (
                    <DerivedField
                        label="Amount Due"
                        value={amountDue === null ? null : formatMoney(amountDue)}
                        placeholder="Auto calculated"
                        tone="border-emerald-200 bg-emerald-50 text-emerald-700"
                        hint="Amount due is calculated automatically"
                    />
                )}

                {!isDuePaid && (
                    <CalendarInput
                        label="Next Payment Date"
                        required
                        value={data.next_payment_date}
                        onChange={(e) =>
                            setData("next_payment_date", e.target.value)
                        }
                        error={errors.next_payment_date}
                    />
                )}
            </FormSection>

            <FormSection
                icon={IdentificationIcon}
                tone="bg-rose-50 text-rose-600"
                title="Primary Contact"
                action={
                    <Toggle
                        checked={hasPrimaryContact}
                        onChange={setHasPrimaryContact}
                        ariaLabel="Primary contact"
                    />
                }
            >
                {hasPrimaryContact && (
                    <>
                        <TextInput
                            label="Contact Person"
                            value={data.contact_person}
                            onChange={(e) =>
                                setData("contact_person", e.target.value)
                            }
                            error={errors.contact_person}
                            placeholder="Full name"
                        />

                        <TextInput
                            label="Contact Email"
                            value={data.contact_email}
                            onChange={(e) =>
                                setData("contact_email", e.target.value)
                            }
                            error={errors.contact_email}
                            placeholder="name@company.com"
                        />

                        <TextInput
                            label="Contact Phone"
                            value={data.contact_phone}
                            onChange={(e) =>
                                setData("contact_phone", e.target.value)
                            }
                            error={errors.contact_phone}
                            placeholder="+880 1XXX-XXXXXX"
                        />
                    </>
                )}
            </FormSection>

            <FormSection
                icon={UsersIcon}
                tone="bg-violet-50 text-violet-600"
                title="Marketer Assignment"
            >
                {isEdit ? (
                    <LockedField label="Company" value={seededCompany?.label} />
                ) : (
                    <SearchableSelect
                        label="Company"
                        required
                        value={data.company_id}
                        onChange={changeCompany}
                        error={errors.company_id}
                        placeholder="Select a company"
                        searchPlaceholder="Search companies"
                        fetchUrl="/v1/admin/projects/companies/search"
                        initialOptions={[]}
                        selectedOption={null}
                    />
                )}

                <SearchableSelect
                    label="Department"
                    required
                    value={data.department_id}
                    onChange={changeDepartment}
                    error={errors.department_id}
                    placeholder={
                        canPickDepartment
                            ? "Select a department"
                            : "Select a company first"
                    }
                    searchPlaceholder="Search departments"
                    fetchUrl={
                        data.company_id
                            ? `/v1/admin/projects/departments/search?company_id=${data.company_id}`
                            : "/v1/admin/projects/departments/search"
                    }
                    initialOptions={[]}
                    selectedOption={departmentSelected}
                />

                <SearchableSelect
                    label="Team"
                    required
                    value={data.team_id}
                    onChange={changeTeam}
                    error={errors.team_id}
                    placeholder={
                        canPickTeam ? "Select a team" : "Select a company first"
                    }
                    searchPlaceholder="Search teams"
                    fetchUrl={teamFetchUrl}
                    initialOptions={[]}
                    selectedOption={teamSelected}
                />

                <SearchableSelect
                    label="Team Member / Marketer"
                    clearable
                    value={data.assigned_employee_id}
                    onChange={(value: string) => setData("assigned_employee_id", value)}
                    error={errors.assigned_employee_id}
                    placeholder={
                        canPickOwner
                            ? "Select a member"
                            : "Select a team first"
                    }
                    searchPlaceholder="Search members"
                    fetchUrl={
                        data.team_id
                            ? `/v1/admin/projects/employees/search?team_id=${data.team_id}`
                            : "/v1/admin/projects/employees/search"
                    }
                    initialOptions={[]}
                    selectedOption={ownerSelected}
                />

                <div className="md:col-span-2">
                    <span className="mb-1 block text-sm font-medium text-gray-700">
                        Project Type
                    </span>
                    <div className="flex flex-wrap gap-3">
                        {PROJECT_TYPES.map((type) => {
                            const active = data.project_type === type.value;

                            return (
                                <label
                                    key={type.value}
                                    className={`flex flex-1 cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition ${
                                        active
                                            ? "border-violet-400 bg-violet-50 font-medium text-violet-700"
                                            : "border-gray-300 bg-white text-gray-700 hover:border-gray-400"
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name="project_type"
                                        value={type.value}
                                        checked={active}
                                        onChange={(e) =>
                                            setData(
                                                "project_type",
                                                e.target.value,
                                            )
                                        }
                                        className="h-4 w-4 border-gray-300 text-violet-600 focus:ring-violet-500"
                                    />
                                    {type.label}
                                </label>
                            );
                        })}
                    </div>
                    {errors.project_type && (
                        <p className="mt-1 text-xs text-red-600">
                            {errors.project_type}
                        </p>
                    )}
                </div>

                <TextInput
                    label="Sales Goal Amount"
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.sales_target}
                    onChange={(e) => setData("sales_target", e.target.value)}
                    error={errors.sales_target}
                    placeholder="e.g. 50000"
                    disabled={!isChallengeBased}
                    required={isChallengeBased}
                />

                <NumberStepper
                    label="Internal Sales Target Duration"
                    value={data.target_months}
                    onChange={(value) => setData("target_months", value)}
                    min={1}
                    max={255}
                    error={errors.target_months}
                    disabled={!isChallengeBased}
                    required={isChallengeBased}
                />

                <NumberStepper
                    label="Days"
                    value={data.target_days}
                    onChange={(value) => setData("target_days", value)}
                    min={0}
                    max={365}
                    error={errors.target_days}
                    disabled={!isChallengeBased}
                />

                {isChallengeBased ? (
                    <DerivedField
                        label="Target Deadline"
                        value={targetDeadline}
                        placeholder="Auto calculated"
                        tone="border-violet-200 bg-violet-50 text-violet-700"
                        hint="Deadline = onboarding date + duration. Milestones are monthly."
                    />
                ) : (
                    <div className="md:col-span-3">
                        <p className="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                            <ExclamationTriangleIcon className="mt-0.5 h-4 w-4 shrink-0" />
                            <span>
                                Regular projects have no sales goal. Switch to{" "}
                                <span className="font-medium">
                                    Challenge Based
                                </span>{" "}
                                to set sales target and monthly milestones.
                            </span>
                        </p>
                    </div>
                )}
            </FormSection>

            <div className="flex justify-end gap-3 pb-2">
                <Link href="/admin/projects">
                    <Button variant="secondary" type="button">
                        Cancel
                    </Button>
                </Link>
                <Button type="submit" disabled={processing}>
                    <CheckIcon className="h-4 w-4" />
                    {isEdit ? "Save changes" : "Create Project"}
                </Button>
            </div>
        </form>
    );
}
