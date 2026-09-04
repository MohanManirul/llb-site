import { useEffect, useState, type ReactNode } from "react";
import { ArrowPathIcon } from "@heroicons/react/24/outline";
import DashboardLayout from "@/components/common/DashboardLayout";
import PageHeader from '@/components/common/PageHeader';
import EmployeeFormComponent from "../components/EmployeeFormComponent";
import api from "@/lib/api-client";
import { flash, errorMessage } from "@/lib/flash";
import type { Employee } from "../types";

interface EmployeesEditProps {
    employeeId: string;
}

export default function EmployeesEdit({ employeeId }: EmployeesEditProps) {
    const [employee, setEmployee] = useState<Employee | null>(null);

    useEffect(() => {
        let cancelled = false;

        api.get(`/admin/employees/${employeeId}`)
            .then(({ data }) => {
                if (!cancelled) setEmployee(data.result);
            })
            .catch((error) => {
                flash.error(errorMessage(error, "Could not load the employee."));
            });

        return () => {
            cancelled = true;
        };
    }, [employeeId]);

    return (
        <>
            <PageHeader
                title="Edit Employee" />

            {employee ? (
                <EmployeeFormComponent employee={employee} />
            ) : (
                <div className="flex justify-center p-10">
                    <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                </div>
            )}
        </>
    );
}

EmployeesEdit.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
