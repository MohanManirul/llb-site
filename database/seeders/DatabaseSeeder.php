<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            DesignationSeeder::class,
            CompanySeeder::class,
            DepartmentSeeder::class,
            EmployeeSeeder::class,
            TeamsSeeder::class,
            ClientSeeder::class,
            // ProjectSeeder::class,
        ]);
    }
}
