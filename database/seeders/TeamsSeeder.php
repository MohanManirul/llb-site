<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamsSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->pluck('id', 'name');

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found — run CompanySeeder first.');

            return;
        }

        $teamsCreated = 0;
        $membershipsCreated = 0;

        $teamDefinitions = [
            'StepUp Technologies Ltd.' => [
                ['name' => 'Product Development Alpha', 'department' => 'Engineering'],
                ['name' => 'Product Development Beta', 'department' => 'Engineering'],
                ['name' => 'HR Operations', 'department' => 'Human Resources'],
                ['name' => 'Finance Planning', 'department' => 'Finance & Accounts'],
                ['name' => 'Digital Marketing Campaign', 'department' => 'Digital Marketing'],
            ],
            'Boneek Commerce Ltd.' => [
                ['name' => 'Backend Services', 'department' => 'Engineering'],
                ['name' => 'Customer Success', 'department' => 'Customer Support'],
                ['name' => 'Sales Operations', 'department' => 'Sales'],
                ['name' => 'Call Center Operations', 'department' => 'Call Center'],
                ['name' => 'Accounts Receivable', 'department' => 'Finance & Accounts'],
            ],
            'StepUp Logistics Ltd.' => [
                ['name' => 'Route Optimization', 'department' => 'Operations'],
                ['name' => 'Logistics Support', 'department' => 'Customer Support'],
                ['name' => 'Supply Chain', 'department' => 'Operations'],
                ['name' => 'Sales & Partnerships', 'department' => 'Sales'],
                ['name' => 'Warehouse Management', 'department' => 'Operations'],
            ],
        ];

        foreach ($teamDefinitions as $companyName => $teams) {
            $companyId = $companies[$companyName] ?? null;

            if ($companyId === null) {
                $this->command->warn("Company '{$companyName}' not found.");

                continue;
            }

            $companyEmployees = Employee::query()
                ->where('company_id', $companyId)
                ->with('user')
                ->get();

            if ($companyEmployees->isEmpty()) {
                $this->command->warn("No employees found for {$companyName}");

                continue;
            }

            foreach ($teams as $teamData) {
                $department = Department::query()
                    ->where('company_id', $companyId)
                    ->firstWhere('name', $teamData['department']);

                if ($department === null) {
                    $this->command->warn("Department '{$teamData['department']}' not found in {$companyName}");

                    continue;
                }

                $team = Team::firstOrCreate(
                    ['company_id' => $companyId, 'name' => $teamData['name']],
                    [
                        'department_id' => $department->id,
                        'description' => "Team responsible for {$teamData['name']}",
                        'is_active' => true,
                    ],
                );

                $teamsCreated++;

                $numMembers = rand(3, 5);
                $selectedEmployees = $companyEmployees->random(min($numMembers, $companyEmployees->count()));

                foreach ($selectedEmployees as $index => $employee) {
                    $role = $index === 0 ? TeamRole::Leader : TeamRole::Member;

                    $team->members()->attach($employee->id, [
                        'role' => $role->value,
                    ]);

                    $membershipsCreated++;
                }
            }
        }

        $this->command->info("{$teamsCreated} teams and {$membershipsCreated} memberships created");
    }
}
