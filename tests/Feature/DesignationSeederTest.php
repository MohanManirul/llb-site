<?php

namespace Tests\Feature;

use App\Models\Designation;
use Database\Seeders\DesignationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DesignationSeeder runs on live installs, so what it must not do matters as
 * much as what it does: no truncate, no faker, and a second run that changes
 * nothing.
 */
class DesignationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_real_designations_and_can_be_re_run(): void
    {
        $this->seed(DesignationSeeder::class);

        $this->assertSame(count(DesignationSeeder::NAMES), Designation::query()->count());

        $edited = Designation::query()->firstWhere('name', 'Web Developer');
        $edited->update(['is_active' => false]);

        $this->seed(DesignationSeeder::class);

        $this->assertSame(count(DesignationSeeder::NAMES), Designation::query()->count());
        $this->assertFalse(
            $edited->fresh()->is_active,
            'a designation someone has edited is left alone',
        );
    }
}
