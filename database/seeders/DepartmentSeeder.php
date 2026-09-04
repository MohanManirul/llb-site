<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public const array NAMES = [
        'Human Resources' => 'Hiring, payroll and everything about the people.',
        'Finance & Accounts' => 'Billing, collections and the books.',
        'Engineering' => 'Builds and runs the products.',
        'Digital Marketing' => 'Campaigns, content and the funnel.',
        'Sales' => 'Owns the pipeline and the targets.',
        'Customer Support' => 'Answers customers after the sale.',
        'Operations' => 'Logistics, procurement and day-to-day running.',
    ];

    public function run(): void
    {
        $companies = Company::query()
            ->whereIn('name', array_column(CompanySeeder::COMPANIES, 'name'))
            ->get(['id', 'name']);

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found — run CompanySeeder first.');

            return;
        }

        foreach ($companies as $company) {
            foreach (self::NAMES as $name => $description) {
                Department::firstOrCreate(
                    ['company_id' => $company->id, 'name' => $name],
                    ['description' => $description, 'is_active' => true],
                );
            }
        }

        $this->command->info(
            $companies->count().' companies × '.count(self::NAMES).' departments are in place',
        );
    }
}
