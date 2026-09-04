<?php

namespace Database\Seeders;

use App\Enums\BusinessStatus;
use App\Enums\HealthStatus;
use App\Enums\ProjectType;
use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->get(['id', 'name']);
        $clients = Client::query()->get(['id', 'name']);
        $teams = Team::query()->with('members')->get();
        $employees = Employee::query()->get(['id']);

        if ($companies->isEmpty() || $clients->isEmpty() || $teams->isEmpty() || $employees->isEmpty()) {
            $this->command->warn('Required data not found — ensure Company, Client, Team, and Employee seeders have run.');

            return;
        }

        $projectsCreated = 0;
        $company = $companies->first();

        $departments = Department::query()
            ->where('company_id', $company->id)
            ->get(['id', 'name']);

        $projectDefinitions = [
            [
                'project_name' => 'E-Commerce Platform Revamp',
                'business_name' => 'Zahir Textiles Ltd.',
                'description' => 'Complete redesign and modernization of e-commerce platform for textile products.',
                'package_amount' => 150000,
                'total_amount' => 150000,
                'sales_target' => 500000,
                'project_type' => ProjectType::ChallengeBased,
                'contract_months' => 6,
                'business_status' => BusinessStatus::CampaignRunning,
            ],
            [
                'project_name' => 'Digital Marketing Campaign',
                'business_name' => 'Farida RMG Solutions',
                'description' => 'Multi-channel digital marketing campaign for brand awareness.',
                'package_amount' => 75000,
                'total_amount' => 75000,
                'sales_target' => 300000,
                'project_type' => ProjectType::ChallengeBased,
                'contract_months' => 3,
                'business_status' => BusinessStatus::CampaignRunning,
            ],
            [
                'project_name' => 'Supply Chain Optimization',
                'business_name' => 'Karim & Co. Jute Industries',
                'description' => 'Process optimization and supply chain digitalization.',
                'package_amount' => 120000,
                'total_amount' => 120000,
                'project_type' => ProjectType::Regular,
                'contract_months' => 4,
                'business_status' => BusinessStatus::BusinessSetup,
            ],
            [
                'project_name' => 'Export Market Expansion',
                'business_name' => 'Rana Leather Works',
                'description' => 'Market research and strategy for European export markets.',
                'package_amount' => 200000,
                'total_amount' => 200000,
                'sales_target' => 1000000,
                'project_type' => ProjectType::ChallengeBased,
                'contract_months' => 8,
                'business_status' => BusinessStatus::BusinessSetup,
            ],
            [
                'project_name' => 'Quality Assurance Program',
                'business_name' => 'Rahman Seafood Export',
                'description' => 'Implementation of international quality standards and certifications.',
                'package_amount' => 90000,
                'total_amount' => 90000,
                'project_type' => ProjectType::Regular,
                'contract_months' => 5,
                'business_status' => BusinessStatus::CampaignOff,
            ],
            [
                'project_name' => 'Social Media Growth Strategy',
                'business_name' => 'Nasrin Fashion Group',
                'description' => 'Influencer partnerships and social media content strategy.',
                'package_amount' => 85000,
                'total_amount' => 85000,
                'sales_target' => 400000,
                'project_type' => ProjectType::ChallengeBased,
                'contract_months' => 6,
                'business_status' => BusinessStatus::CampaignRunning,
            ],
            [
                'project_name' => 'Farm to Market Initiative',
                'business_name' => 'Hakim Agricultural Exports',
                'description' => 'Direct-to-consumer sales platform development.',
                'package_amount' => 110000,
                'total_amount' => 110000,
                'sales_target' => 600000,
                'project_type' => ProjectType::ChallengeBased,
                'contract_months' => 6,
                'business_status' => BusinessStatus::CampaignRunning,
            ],
            [
                'project_name' => 'Production Capacity Enhancement',
                'business_name' => 'Ismail Ceramics Industries',
                'description' => 'Facility expansion and automation upgrade project.',
                'package_amount' => 250000,
                'total_amount' => 250000,
                'project_type' => ProjectType::Regular,
                'contract_months' => 12,
                'business_status' => BusinessStatus::BusinessSetup,
            ],
            [
                'project_name' => 'B2B Sales Channel Development',
                'business_name' => 'Biplob Steel & Engineering',
                'description' => 'Enterprise sales team training and CRM implementation.',
                'package_amount' => 130000,
                'total_amount' => 130000,
                'sales_target' => 800000,
                'project_type' => ProjectType::ChallengeBased,
                'contract_months' => 5,
                'business_status' => BusinessStatus::CampaignRunning,
            ],
            [
                'project_name' => 'Global Handicrafts Marketplace',
                'business_name' => 'Salma Handicrafts Collective',
                'description' => 'Online marketplace development for traditional crafts.',
                'package_amount' => 95000,
                'total_amount' => 95000,
                'sales_target' => 350000,
                'project_type' => ProjectType::ChallengeBased,
                'contract_months' => 4,
                'business_status' => BusinessStatus::BusinessSetup,
            ],
            [
                'project_name' => 'Product Line Expansion',
                'business_name' => 'Omar Pharmaceuticals Ltd.',
                'description' => 'Market research and product development for new therapeutic areas.',
                'package_amount' => 180000,
                'total_amount' => 180000,
                'sales_target' => 900000,
                'project_type' => ProjectType::ChallengeBased,
                'contract_months' => 9,
                'business_status' => BusinessStatus::BusinessSetup,
            ],
            [
                'project_name' => 'Cold Chain Infrastructure',
                'business_name' => 'Fatima Frozen Foods',
                'description' => 'Cold storage and logistics network optimization.',
                'package_amount' => 220000,
                'total_amount' => 220000,
                'project_type' => ProjectType::Regular,
                'contract_months' => 8,
                'business_status' => BusinessStatus::CampaignOff,
            ],
            [
                'project_name' => 'Retail Distribution Network',
                'business_name' => 'Rashid Footwear Company',
                'description' => 'National retail expansion and distribution network setup.',
                'package_amount' => 165000,
                'total_amount' => 165000,
                'sales_target' => 750000,
                'project_type' => ProjectType::ChallengeBased,
                'contract_months' => 7,
                'business_status' => BusinessStatus::CampaignRunning,
            ],
            [
                'project_name' => 'Sustainability Initiative',
                'business_name' => 'Jahanara Plastics Solutions',
                'description' => 'Eco-friendly product development and certification.',
                'package_amount' => 140000,
                'total_amount' => 140000,
                'project_type' => ProjectType::Regular,
                'contract_months' => 6,
                'business_status' => BusinessStatus::OnHold,
            ],
            [
                'project_name' => 'International Trade Expansion',
                'business_name' => 'Samir Global Trading',
                'description' => 'Market entry strategy for Southeast Asian markets.',
                'package_amount' => 195000,
                'total_amount' => 195000,
                'sales_target' => 1200000,
                'project_type' => ProjectType::ChallengeBased,
                'contract_months' => 10,
                'business_status' => BusinessStatus::BusinessSetup,
            ],
        ];

        foreach ($projectDefinitions as $projectData) {
            $client = $clients->random();
            $team = $teams->random();
            $department = $departments->random();
            $employee = $employees->random();

            $startDate = now()->subMonths(rand(2, 6));
            $contractMonths = $projectData['contract_months'] ?? 6;
            $endDate = $startDate->copy()->addMonths($contractMonths);

            $targetStartDate = $startDate->copy()->addDays(rand(5, 30));
            $targetMonths = $contractMonths - 1;
            $targetDeadline = $targetStartDate->copy()->addMonths($targetMonths);

            $nextPaymentDate = now()->addDays(rand(7, 30));
            $lastPaymentDate = rand(0, 1) ? now()->subDays(rand(5, 30)) : null;
            $amountPaid = $projectData['project_type'] === ProjectType::ChallengeBased
                ? rand(0, (int) $projectData['total_amount'] * 0.5)
                : rand(0, (int) $projectData['total_amount'] * 0.3);

            $healthStatus = $projectData['project_type'] === ProjectType::Regular
                ? HealthStatus::Upcoming
                : HealthStatus::cases()[array_rand(HealthStatus::cases())];

            Project::create([
                'company_id' => $company->id,
                'department_id' => $department->id,
                'client_id' => $client->id,
                'team_id' => $team->id,
                'assigned_employee_id' => $employee->id,
                'project_name' => $projectData['project_name'],
                'business_name' => $projectData['business_name'],
                'website_url' => 'https://' . strtolower(str_replace(' ', '', $projectData['business_name'])) . '.com.bd',
                'description' => $projectData['description'],
                'start_date' => $startDate->toDateString(),
                'contract_months' => $contractMonths,
                'contract_days' => 0,
                'end_date' => $endDate->toDateString(),
                'contact_person' => 'Contact Person',
                'contact_email' => $client->email,
                'contact_phone' => $client->phone,
                'package_amount' => $projectData['package_amount'],
                'total_amount' => $projectData['total_amount'],
                'amount_paid' => $amountPaid,
                'next_payment_date' => $nextPaymentDate->toDateString(),
                'last_payment_date' => $lastPaymentDate?->toDateString(),
                'project_type' => $projectData['project_type'],
                'sales_target' => $projectData['sales_target'] ?? 0,
                'target_start_date' => $projectData['project_type'] === ProjectType::ChallengeBased ? $targetStartDate->toDateString() : null,
                'target_months' => $projectData['project_type'] === ProjectType::ChallengeBased ? $targetMonths : null,
                'target_days' => 0,
                'target_deadline' => $projectData['project_type'] === ProjectType::ChallengeBased ? $targetDeadline->toDateString() : null,
                'achieved_sales' => $projectData['project_type'] === ProjectType::ChallengeBased ? rand(0, (int) $projectData['sales_target']) : 0,
                'health_status' => $healthStatus,
                'health_evaluated_at' => now(),
                'business_status' => $projectData['business_status'],
            ]);

            $projectsCreated++;
        }

        $this->command->info("{$projectsCreated} projects created");
    }
}
