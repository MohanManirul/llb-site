<?php

namespace Database\Seeders;

use App\Models\College;
use Illuminate\Database\Seeder;

/**
 * Production-safe like PermissionSeeder and AcademicStructureSeeder: additive
 * only, keyed on slug, never sync, never truncate. Safe to re-run against a
 * live database.
 *
 * These are placeholder rows for local development only. The real 79
 * NU-affiliated law colleges are entered through Admin -> Colleges.
 */
class CollegeSeeder extends Seeder
{
    private const array COLLEGES = [
        [
            'slug' => 'demo-law-college-dhaka',
            'name_bn' => 'ডেমো ল কলেজ, ঢাকা',
            'name_en' => 'Demo Law College, Dhaka',
            'district_bn' => 'ঢাকা',
            'district_en' => 'Dhaka',
            'sort_order' => 1,
        ],
        [
            'slug' => 'demo-law-college-chattogram',
            'name_bn' => 'ডেমো ল কলেজ, চট্টগ্রাম',
            'name_en' => 'Demo Law College, Chattogram',
            'district_bn' => 'চট্টগ্রাম',
            'district_en' => 'Chattogram',
            'sort_order' => 2,
        ],
        [
            'slug' => 'demo-law-college-rajshahi',
            'name_bn' => 'ডেমো ল কলেজ, রাজশাহী',
            'name_en' => 'Demo Law College, Rajshahi',
            'district_bn' => 'রাজশাহী',
            'district_en' => 'Rajshahi',
            'sort_order' => 3,
        ],
        [
            'slug' => 'demo-law-college-khulna',
            'name_bn' => 'ডেমো ল কলেজ, খুলনা',
            'name_en' => 'Demo Law College, Khulna',
            'district_bn' => 'খুলনা',
            'district_en' => 'Khulna',
            'sort_order' => 4,
        ],
        [
            'slug' => 'demo-law-college-sylhet',
            'name_bn' => 'ডেমো ল কলেজ, সিলেট',
            'name_en' => 'Demo Law College, Sylhet',
            'district_bn' => 'সিলেট',
            'district_en' => 'Sylhet',
            'sort_order' => 5,
        ],
    ];

    public function run(): void
    {
        foreach (self::COLLEGES as $college) {
            College::firstOrCreate(
                ['slug' => $college['slug']],
                array_merge($college, ['is_active' => true]),
            );
        }

        $this->command->info(count(self::COLLEGES).' demo colleges are in place');
    }
}
