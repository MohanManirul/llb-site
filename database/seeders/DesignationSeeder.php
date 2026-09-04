<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public const array NAMES = [
        'Chief Executive Officer (CEO)',
        'Operations Manager',
        'Digital Marketing Manager',
        'SEO Specialist',
        'Social Media Manager',
        'Senior Software Engineer',
        'Software',
        'Web Developer',
        'PPC Specialist',
        'Business Development Executive',
        'Call Center Supervisor',
        'Call Center Agent',
    ];

    public function run(): void
    {
        foreach (self::NAMES as $name) {
            Designation::firstOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }

        $this->command->info(count(self::NAMES).' designations are in place');
    }
}
