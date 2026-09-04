<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->pluck('id', 'name');
        $designations = Designation::query()->pluck('id', 'name');

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found — run CompanySeeder first.');

            return;
        }

        $employees = 0;

        foreach (UserSeeder::PEOPLE as $person) {
            $user = User::query()->firstWhere('email', $person['email']);
            $companyId = $companies[$person['company']] ?? null;

            if ($user === null || $companyId === null) {
                $this->command->warn("Skipping {$person['email']}: no user or no company.");

                continue;
            }

            $department = Department::query()
                ->where('company_id', $companyId)
                ->firstWhere('name', $person['department']);

            if ($department === null) {
                $this->command->warn("Skipping {$person['email']}: {$person['department']} is missing.");

                continue;
            }

            Employee::updateOrCreate(
                ['user_id' => $user->id, 'company_id' => $companyId],
                [
                    'department_id' => $department->id,
                    'designation_id' => $designations[$person['designation']] ?? null,
                    'joining_date' => now()->subYear()->toDateString(),
                    'is_active' => true,
                ],
            );

            $employees++;
        }

        $this->command->info("{$employees} employees are in place");
    }
}
