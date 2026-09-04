<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public const array COMPANIES = [
        [
            'name' => 'StepUp Technologies Ltd.',
            'email' => 'info@stepuptech.com.bd',
            'phone' => '+8801713000101',
            'website' => 'https://stepuptech.com.bd',
            'address' => 'House 42, Road 11, Banani, Dhaka 1213, Bangladesh',
        ],
        [
            'name' => 'Boneek Commerce Ltd.',
            'email' => 'info@boneek.com.bd',
            'phone' => '+8801713000102',
            'website' => 'https://boneek.com.bd',
            'address' => 'Level 5, Rangs Babylonia, Bijoy Sarani, Tejgaon, Dhaka 1215, Bangladesh',
        ],
        [
            'name' => 'StepUp Logistics Ltd.',
            'email' => 'info@stepuplogistics.com.bd',
            'phone' => '+8801713000103',
            'website' => 'https://stepuplogistics.com.bd',
            'address' => 'Ayub Trade Centre, GEC Circle, Chattogram 4000, Bangladesh',
        ],
    ];

    public function run(): void
    {
        foreach (self::COMPANIES as $company) {
            Company::firstOrCreate(
                ['name' => $company['name']],
                [
                    'email' => $company['email'],
                    'phone' => $company['phone'],
                    'website' => $company['website'],
                    'address' => $company['address'],
                    'is_active' => true,
                ],
            );
        }

        $this->command->info(count(self::COMPANIES).' companies are in place');
    }
}
