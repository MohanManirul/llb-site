<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * GET /v1/admin/users/search backs the employee form's User picker.
 *
 * Deliberately stricter than the neighbouring search endpoints: it returns
 * every account's name and email, which any logged-in employee could otherwise
 * enumerate.
 */
class UserSearchEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployeeFor(User $user, ?Company $company = null): Employee
    {
        $company ??= Company::firstOrCreate(
            ['name' => 'Acme Corp'],
            ['email' => 'acme@example.com'],
        );
        $department = Department::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Engineering'],
        );

        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'designation_id' => Designation::firstOrCreate(['name' => 'Engineer'])->id,
            'is_active' => true,
        ]);
    }

    /**
     * The disabled Phone preview on the employee form breaks silently without
     * `phone` riding along on the option payload.
     */
    public function test_the_payload_carries_value_label_description_and_phone(): void
    {
        User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '01711111111',
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/users/search?search=Ada')
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.label', 'Ada Lovelace')
            ->assertJsonPath('result.0.description', 'ada@example.com')
            ->assertJsonPath('result.0.phone', '01711111111')
            ->assertJsonPath('result.0.image_url', null)
            ->assertJsonPath('result.0.thumbnail_url', null)
            ->assertJsonStructure(['result' => [['value', 'label', 'description', 'phone', 'image_url', 'thumbnail_url']]]);
    }

    public function test_it_searches_by_name_or_email(): void
    {
        User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
        User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->getJson('/v1/admin/users/search?search=grace@')
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.label', 'Grace Hopper');

        $this->actingAs($admin)
            ->getJson('/v1/admin/users/search?search=Lovelace')
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.label', 'Ada Lovelace');
    }

    public function test_users_who_are_already_employees_are_excluded(): void
    {
        $taken = User::factory()->create(['name' => 'Taken Person']);
        User::factory()->create(['name' => 'Free Person']);

        $this->makeEmployeeFor($taken);

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/users/search?search=Person')
            ->assertOk();

        $labels = array_column($response->json('result'), 'label');

        $this->assertContains('Free Person', $labels);
        $this->assertNotContains('Taken Person', $labels);
    }

    public function test_the_exclusion_is_scoped_to_the_requested_company(): void
    {
        $person = User::factory()->create(['name' => 'Taken Person']);
        $acme = Company::firstOrCreate(['name' => 'Acme Corp'], ['email' => 'acme@example.com']);
        $globex = Company::create(['name' => 'Globex', 'email' => 'globex@example.com']);

        $this->makeEmployeeFor($person, $acme);

        $admin = User::factory()->create();

        $this->assertNotContains('Taken Person', array_column(
            $this->actingAs($admin)
                ->getJson("/v1/admin/users/search?search=Taken&company_id={$acme->id}")
                ->assertOk()
                ->json('result'),
            'label',
        ));

        $this->assertContains('Taken Person', array_column(
            $this->actingAs($admin)
                ->getJson("/v1/admin/users/search?search=Taken&company_id={$globex->id}")
                ->assertOk()
                ->json('result'),
            'label',
        ));
    }

    public function test_a_soft_deleted_employee_still_excludes_its_user(): void
    {
        $taken = User::factory()->create(['name' => 'Taken Person']);
        $employee = $this->makeEmployeeFor($taken);
        $employee->delete();

        $response = $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/users/search?search=Taken&company_id={$employee->company_id}")
            ->assertOk();

        $this->assertNotContains('Taken Person', array_column($response->json('result'), 'label'));
    }

    public function test_keep_user_id_survives_the_exclusion(): void
    {
        $taken = User::factory()->create(['name' => 'Taken Person']);
        $this->makeEmployeeFor($taken);

        $response = $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/users/search?search=Taken&keep_user_id={$taken->id}")
            ->assertOk();

        $this->assertContains('Taken Person', array_column($response->json('result'), 'label'));
    }

    public function test_it_caps_the_option_list_at_ten(): void
    {
        User::factory()->count(15)->create();

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/users/search')
            ->assertOk()
            ->assertJsonCount(10, 'result');
    }

    /**
     * TestCase::actingAs silently promotes a role-less user to super-admin, so
     * the gate has to be checked with an explicit role or it passes for the
     * wrong reason.
     */
    public function test_a_role_without_the_permission_is_forbidden(): void
    {
        Permission::findOrCreate('view employees', 'web');
        $role = Role::findOrCreate('viewer', 'web');
        $role->givePermissionTo('view employees');

        $viewer = User::factory()->create();
        $viewer->assignRole($role);

        $this->actingAs($viewer)
            ->getJson('/v1/admin/users/search')
            ->assertForbidden();
    }

    public function test_a_role_with_employees_create_is_allowed(): void
    {
        Permission::findOrCreate('create employees', 'web');
        $role = Role::findOrCreate('hr', 'web');
        $role->givePermissionTo('create employees');

        $hr = User::factory()->create();
        $hr->assignRole($role);

        $this->actingAs($hr)
            ->getJson('/v1/admin/users/search')
            ->assertOk();
    }

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/v1/admin/users/search')->assertUnauthorized();
    }
}
