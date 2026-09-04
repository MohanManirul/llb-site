<?php

namespace Database\Seeders;

use App\Enums\BusinessStatus;
use App\Enums\ProjectType;
use App\Enums\TeamRole;
use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\SalesReport;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo volume for looking at the dashboard, deliberately kept OUT of
 * DatabaseSeeder. That one is a real starting install and is safe to re-run on
 * a live database; this one invents clients, teams, projects and sales that
 * never happened.
 *
 *     php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    private const PROJECTS = 20;

    private const WEEKS = 26;

    private const DAYS = self::WEEKS * 7;

    private const CLIENTS = 8;

    /** @var array<int, Team> */
    private array $teams = [];

    /** Trading names, so a row does not read "Acme · Acme" against its client. */
    private const BUSINESSES = [
        'Zenvora', 'Northwind', 'Bluepeak', 'Cedarline', 'Harborview',
        'Ironleaf', 'Kestrel', 'Lumenwork', 'Marbleway', 'Novagrid',
        'Oakfield', 'Pinecrest', 'Quarrystone', 'Riversend', 'Silverlark',
        'Thornbury', 'Umbercrest', 'Vantagepoint', 'Westmoor', 'Yellowstone',
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->warn('DemoDataSeeder refuses to run in production.');

            return;
        }

        $companies = Company::with('departments')->get();
        $employees = Employee::all();

        if ($companies->isEmpty() || $employees->isEmpty()) {
            $this->command->warn('Run DatabaseSeeder first — companies and employees are needed.');

            return;
        }

        $clients = $this->seedClients();

        $statuses = BusinessStatus::cases();
        $created = 0;

        foreach (range(1, self::PROJECTS) as $index) {
            $company = $companies[$index % $companies->count()];
            $department = $company->departments->isNotEmpty()
                ? $company->departments[$index % $company->departments->count()]
                : Department::where('company_id', $company->id)->first();

            if ($department === null) {
                continue;
            }

            $client = $clients[$index % $clients->count()];
            $employee = $employees[$index % $employees->count()];
            $team = $this->teamFor($department, $employees);

            $start = Carbon::today()->subWeeks(self::WEEKS)->addDays($index * 3);
            $months = 12;
            $package = 60000 + ($index % 8) * 25000;
            $paid = (int) round($package * [1, 0.75, 0.5, 0.25][$index % 4]);

            $project = Project::updateOrCreate(
                ['project_name' => "Demo Project {$index}"],
                [
                    'company_id' => $company->id,
                    'department_id' => $department->id,
                    'client_id' => $client->id,
                    'team_id' => $team->id,
                    // Every fourth one is left unassigned so the leader view has
                    // something in its "unassigned projects" list.
                    'assigned_employee_id' => $index % 4 === 0 ? null : $employee->id,
                    'business_name' => self::BUSINESSES[($index - 1) % count(self::BUSINESSES)],
                    'start_date' => $start->toDateString(),
                    'contract_months' => $months,
                    'end_date' => $start->copy()->addMonths($months)->toDateString(),
                    'package_amount' => $package,
                    'amount_paid' => $paid,
                    'next_payment_date' => Carbon::today()->addDays(($index % 10) * 6)->toDateString(),
                    'project_type' => $index % 3 === 0 ? ProjectType::ChallengeBased : ProjectType::Regular,
                    'sales_target' => 240000 + ($index % 6) * 120000,
                    'target_start_date' => $start->toDateString(),
                    'target_months' => $months,
                    'target_deadline' => $start->copy()->addMonths($months)->toDateString(),
                    'business_status' => $statuses[$index % count($statuses)],
                ]
            );

            $this->seedDailySales($project, $index);
            $this->seedNotes($project, $index);
            $created++;
        }

        $this->command->info("{$created} demo projects with ".(self::DAYS + 1).' days of sales are in place');
    }

    /** @param  \Illuminate\Support\Collection<int, Employee>  $employees */
    private function teamFor(Department $department, $employees): Team
    {
        if (isset($this->teams[$department->id])) {
            return $this->teams[$department->id];
        }

        $team = Team::withTrashed()->updateOrCreate(
            [
                'company_id' => $department->company_id,
                'department_id' => $department->id,
                'name' => 'Demo Team — '.$department->name,
            ],
            ['description' => 'Seeded team for the demo dataset.', 'is_active' => true, 'deleted_at' => null]
        );

        $staff = $employees->where('department_id', $department->id)->values();

        if ($staff->isEmpty()) {
            $staff = $employees->where('company_id', $department->company_id)->values();
        }

        if ($staff->isNotEmpty()) {
            $team->members()->syncWithoutDetaching(
                $staff->take(4)->mapWithKeys(fn (Employee $member, int $position) => [
                    $member->id => ['role' => $position === 0 ? TeamRole::Leader->value : TeamRole::Member->value],
                ])->all()
            );
        }

        return $this->teams[$department->id] = $team;
    }

    /** @return Collection<int, Client> */
    private function seedClients(): Collection
    {
        foreach (range(1, self::CLIENTS) as $index) {
            Client::updateOrCreate(
                ['email' => "demo.client{$index}@example.test"],
                [
                    'name' => 'Demo Client '.$index,
                    'password' => 'password',
                    'phone' => '0170000'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'address' => "House {$index}, Road ".($index * 3).', Dhaka',
                    'is_active' => true,
                ]
            );
        }

        return Client::where('is_active', true)->get();
    }

    private function seedDailySales(Project $project, int $index): void
    {
        $dailyTarget = (float) $project->sales_target / 365;
        $performance = [1.25, 0.95, 0.7, 0.4][$index % 4];

        SalesReport::where('project_id', $project->id)->delete();

        $rows = [];

        for ($back = self::DAYS; $back >= 0; $back--) {
            $date = Carbon::today()->subDays($back);
            $swing = 0.55 + (($index * 97 + $back * 53 + ($back * $back) % 37) % 95) / 100;
            $weekday = $date->isWeekend() ? 0.62 : 1.0;
            $sales = round($dailyTarget * $performance * $swing * $weekday, 2);

            $rows[] = [
                'company_id' => $project->company_id,
                'project_id' => $project->id,
                'week_start' => $date->toDateString(),
                'week_end' => $date->toDateString(),
                'total_sales' => $sales,
                'total_amount_spent' => round($sales * (0.16 + (($index + $back) % 14) / 100), 2),
                'total_order_quantity' => (int) max(1, round($sales / 1200)),
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            SalesReport::insert($chunk);
        }
    }

    private function seedNotes(Project $project, int $index): void
    {
        if ($index % 3 !== 0) {
            return;
        }

        ProjectNote::updateOrCreate(
            ['project_id' => $project->id, 'note' => 'Weekly review logged for the demo dataset.'],
            ['company_id' => $project->company_id, 'user_id' => $project->assignedEmployee?->user_id]
        );
    }
}
