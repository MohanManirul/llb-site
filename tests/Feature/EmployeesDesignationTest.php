<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The employee form's designation picker used to offer whatever text other
 * employees already carried, and let you type a brand new one. Designations are
 * their own CRUD now: the options come from /v1/admin/designations/search and the
 * employee stores a designation_id, so the form can no longer invent one.
 */
class EmployeesDesignationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Acme Corp',
            'code' => 'ACME',
            'email' => 'acme@example.com',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Engineering',
            'code' => 'ENG',
            'is_active' => true,
        ]);
    }

    private function makeEmployee(
        string $name,
        Department $department,
        Designation $designation,
    ): Employee {
        return Employee::create([
            'user_id' => User::factory()->create([
                'name' => $name,
                'email' => strtolower($name).'@example.com',
            ])->id,
            'company_id' => $department->company_id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'is_active' => true,
        ]);
    }

    /** The picker is a SearchableSelect, so it needs {value,label} options. */
    public function test_the_options_come_from_the_designations_crud(): void
    {
        Designation::create(['name' => 'Manager', 'is_active' => true]);
        Designation::create(['name' => 'Director', 'is_active' => true]);
        // Retired designations must not show up as a pick.
        Designation::create(['name' => 'Intern', 'is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/designations/search')
            ->assertOk()
            ->assertJsonCount(2, 'result')
            ->assertJsonFragment(['label' => 'Director'])
            ->assertJsonFragment(['label' => 'Manager'])
            ->assertJsonMissing(['label' => 'Intern']);
    }

    /**
     * The employee form's cascade: a company on its own leaves the whole list,
     * because designations aren't tied to a company at all.
     */
    public function test_a_company_on_its_own_does_not_narrow_the_options(): void
    {
        Designation::create(['name' => 'Manager', 'is_active' => true]);
        Designation::create(['name' => 'Director', 'is_active' => true]);

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/designations/search?company_id={$this->company->id}")
            ->assertOk()
            ->assertJsonCount(2, 'result');
    }

    /** Once a department is picked, only what its employees hold is offered. */
    public function test_a_department_narrows_the_options_to_the_ones_its_employees_hold(): void
    {
        $manager = Designation::create(['name' => 'Manager', 'is_active' => true]);
        Designation::create(['name' => 'Director', 'is_active' => true]);

        $this->makeEmployee('Alice', $this->department, $manager);

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/designations/search?company_id={$this->company->id}&department_id={$this->department->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.value', $manager->id)
            ->assertJsonPath('result.0.label', 'Manager');
    }

    /** Another department's picks must not leak into this one's list. */
    public function test_the_narrowing_is_scoped_to_the_department_asked_for(): void
    {
        $manager = Designation::create(['name' => 'Manager', 'is_active' => true]);
        $director = Designation::create(['name' => 'Director', 'is_active' => true]);

        $sales = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Sales',
            'code' => 'SAL',
            'is_active' => true,
        ]);

        $this->makeEmployee('Alice', $this->department, $manager);
        $this->makeEmployee('Bob', $sales, $director);

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/designations/search?company_id={$this->company->id}&department_id={$sales->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.label', 'Director');
    }

    /**
     * A department nobody works in yet falls back to the full list — narrowing
     * it to nothing would leave its first employee with nothing to pick.
     */
    public function test_an_empty_department_falls_back_to_every_designation(): void
    {
        Designation::create(['name' => 'Manager', 'is_active' => true]);
        Designation::create(['name' => 'Director', 'is_active' => true]);

        $empty = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Support',
            'code' => 'SUP',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/designations/search?company_id={$this->company->id}&department_id={$empty->id}")
            ->assertOk()
            ->assertJsonCount(2, 'result');
    }

    public function test_the_options_can_be_searched_by_name(): void
    {
        Designation::create(['name' => 'Manager', 'is_active' => true]);
        Designation::create(['name' => 'Director', 'is_active' => true]);

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/designations/search?search=Direct')
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.label', 'Director');
    }

    public function test_creating_an_employee_stores_the_picked_designation(): void
    {
        $designation = Designation::create(['name' => 'Manager', 'is_active' => true]);
        $alice = User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/employees', [
                'user_id' => $alice->id,
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'designation_id' => $designation->id,
                'is_active' => '1',
            ])
            ->assertCreated()
            ->assertJsonPath('result.designation_id', $designation->id)
            ->assertJsonPath('result.designation', 'Manager');

        $this->assertDatabaseHas('employees', [
            'user_id' => $alice->id,
            'designation_id' => $designation->id,
        ]);
    }

    /** The old form could type a new designation straight in — not anymore. */
    public function test_creating_an_employee_rejects_a_designation_that_does_not_exist(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/employees', [
                'user_id' => User::factory()->create()->id,
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'designation_id' => 9999,
                'is_active' => '1',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('designation_id');

        $this->assertDatabaseMissing('employees', ['email' => 'alice@example.com']);
    }

    /** The endpoint the picker used to call is gone with the feature. */
    public function test_the_old_employee_designations_endpoint_is_gone(): void
    {
        Employee::create([
            'user_id' => User::factory()->create([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ])->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => Designation::create(['name' => 'Manager', 'is_active' => true])->id,
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/employees/designations?company_id={$this->company->id}&department_id={$this->department->id}")
            ->assertNotFound();
    }
}
