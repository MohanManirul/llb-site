import { useState, type ChangeEvent, type FormEvent } from "react";
import { Link, router } from "@inertiajs/react";
import { CheckIcon } from "@heroicons/react/24/outline";
import type { AxiosError } from "axios";
import {
    Button,
    CalendarInput,
    EmailInput,
    SearchableSelect,
    SelectInput,
    Textarea,
    TextInput,
} from "@/components/ui";
import api from "@/lib/api-client";
import { flash, errorMessage, validationErrors } from "@/lib/flash";
import { toDateInput as formatDateValue } from "@/lib/format";
import type { Employee, EmployeeOption, UserOption } from "../types";

interface EmployeeFormComponentProps {
    employee?: Employee;
}

interface EmployeeFormData {
    user_id: string | number;
    company_id: string | number;
    department_id: string | number;
    designation_id: string | number;
    description: string;
    joining_date: string;
    resignation_date: string;
    isActive: boolean;
}

export default function EmployeeForm({ employee }: EmployeeFormComponentProps) {
    const isEdit = Boolean(employee);

    const [data, setDataState] = useState<EmployeeFormData>({
        user_id: employee?.user_id ?? "",
        company_id: employee?.company_id ?? "",
        department_id: employee?.department_id ?? "",
        designation_id: employee?.designation_id ?? "",
        description: employee?.description ?? "",
        joining_date: formatDateValue(employee?.joining_date),
        resignation_date: formatDateValue(employee?.resignation_date),
        isActive: employee?.is_active ?? true,
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    function setData<K extends keyof EmployeeFormData>(field: K, value: EmployeeFormData[K]) {
        setDataState((current) => ({ ...current, [field]: value }));
    }

    const hasResigned = Boolean(data.resignation_date);

    const [userOption, setUserOption] = useState<UserOption | null>(
        employee?.user_id
            ? {
                  value: employee.user_id,
                  label: employee.name ?? "",
                  description: employee.email ?? "",
                  phone: employee.phone ?? "",
              }
            : null,
    );

    const [companyOption, setCompanyOption] = useState<EmployeeOption | null>(
        employee?.company_id
            ? { value: employee.company_id, label: employee.company_name ?? "" }
            : null,
    );
    const [departmentOption, setDepartmentOption] = useState<EmployeeOption | null>(
        employee?.department_id
            ? { value: employee.department_id, label: employee.department_name ?? "" }
            : null,
    );

    const [designationOption, setDesignationOption] = useState<EmployeeOption | null>(
        employee?.designation_id
            ? {
                  value: employee.designation_id,
                  label: employee.designation ?? "",
              }
            : null,
    );

    async function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const payload = {
            user_id: data.user_id,
            company_id: data.company_id,
            department_id: data.department_id,
            designation_id: data.designation_id,
            description: data.description ?? "",
            joining_date: data.joining_date || null,
            resignation_date: data.resignation_date || null,
            is_active: hasResigned ? false : data.isActive,
        };

        try {
            await (isEdit
                ? api.put(`/admin/employees/${employee!.id}`, payload)
                : api.post("/admin/employees", payload));

            flash.success(
                isEdit ? "Employee updated successfully." : "Employee saved.",
            );
            router.visit("/admin/employees");
        } catch (error) {
            if ((error as AxiosError)?.response?.status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(
                    errorMessage(error, "Could not save the employee."),
                );
            }
            setProcessing(false);
        }
    }

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
                                onChange={(value: string, option: EmployeeOption | null) => {
                                    setData("company_id", value);
                                    setCompanyOption(value ? option : null);
                                    setData("user_id", "");
                                    setUserOption(null);
                                    setData("department_id", "");
                                    setDepartmentOption(null);
                                    setData("designation_id", "");
                                    setDesignationOption(null);
                                }}
                                error={errors.company_id}
                                placeholder="Select a Company"
                                searchPlaceholder="Search companies"
                                fetchUrl="/v1/admin/companies/search"
                                initialOptions={[]}
                                selectedOption={companyOption}
                            />

                            <SearchableSelect
                                label="User"
                                required
                                value={data.user_id}
                                onChange={(value: string, option: UserOption | null) => {
                                    setData("user_id", value);
                                    setUserOption(value ? option : null);
                                }}
                                error={errors.user_id}
                                placeholder={
                                    data.company_id
                                        ? "Select a User"
                                        : "Select a company first"
                                }
                                searchPlaceholder="Search users by name or email"
                                fetchUrl={
                                    data.company_id
                                        ? `/v1/admin/users/search?company_id=${data.company_id}` +
                                          (isEdit ? `&keep_user_id=${employee!.user_id}` : "")
                                        : null
                                }
                                initialOptions={[]}
                                selectedOption={userOption}
                            />

                            <SearchableSelect
                                label="Department"
                                required
                                value={data.department_id}
                                onChange={(value: string, option: EmployeeOption | null) => {
                                    setData("department_id", value);
                                    setDepartmentOption(value ? option : null);
                                    setData("designation_id", "");
                                    setDesignationOption(null);
                                }}
                                error={errors.department_id}
                                placeholder="Select a Department"
                                searchPlaceholder="Search departments"
                                fetchUrl={
                                    data.company_id
                                        ? `/v1/admin/departments/search?company_id=${data.company_id}`
                                        : "/v1/admin/departments/search"
                                }
                                initialOptions={[]}
                                selectedOption={departmentOption}
                            />

                            <TextInput
                                label="Name"
                                value={userOption?.label ?? ""}
                                placeholder="Select a user first"
                                disabled
                                readOnly
                            />

                            <SearchableSelect
                                label="Designation"
                                required
                                value={data.designation_id}
                                onChange={(value: string, option: EmployeeOption | null) => {
                                    setData("designation_id", value);
                                    setDesignationOption(value ? option : null);
                                }}
                                error={errors.designation_id}
                                placeholder={
                                    data.company_id
                                        ? "Select a Designation"
                                        : "Select a company first"
                                }
                                searchPlaceholder="Search designations"
                                fetchUrl={
                                    data.company_id
                                        ? `/v1/admin/designations/search?company_id=${data.company_id}` +
                                          (data.department_id
                                              ? `&department_id=${data.department_id}`
                                              : "")
                                        : null
                                }
                                initialOptions={[]}
                                selectedOption={designationOption}
                            />

                            <TextInput
                                label="Phone"
                                value={userOption?.phone ?? ""}
                                placeholder="Select a user first"
                                disabled
                                readOnly
                            />

                            <EmailInput
                                label="Email"
                                name="email"
                                value={userOption?.description ?? ""}
                                placeholder="Select a user first"
                                disabled
                                readOnly
                            />

                            <CalendarInput
                                label="Joining Date"
                                name="joining_date"
                                value={data.joining_date}
                                onChange={(e: ChangeEvent<HTMLInputElement>) =>
                                    setData("joining_date", e.target.value)
                                }
                                error={errors.joining_date}
                            />

                            <CalendarInput
                                label="Resignation Date"
                                name="resignation_date"
                                value={data.resignation_date}
                                onChange={(e: ChangeEvent<HTMLInputElement>) =>
                                    setData("resignation_date", e.target.value)
                                }
                                error={errors.resignation_date}
                            />

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
                        <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                            <SelectInput
                                label="Status"
                                value={hasResigned || !data.isActive ? "0" : "1"}
                                onChange={(e: ChangeEvent<HTMLSelectElement>) =>
                                    setData("isActive", e.target.value === "1")
                                }
                                disabled={hasResigned}
                                hint={
                                    hasResigned
                                        ? "A resignation date keeps the employee inactive."
                                        : undefined
                                }
                                error={errors.is_active}
                            >
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </SelectInput>
                        </div>
                    </section>
                </div>
            </div>

            <div className="flex justify-end gap-3">
                <Link href="/admin/employees">
                    <Button variant="secondary" type="button">
                        Cancel
                    </Button>
                </Link>
                <Button type="submit" disabled={processing}>
                    <CheckIcon className="h-4 w-4" />
                    {isEdit ? "Save changes" : "Create Employee"}
                </Button>
            </div>
        </form>
    );
}
