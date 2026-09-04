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
 * An employee is a user who works here.
 */
class EmployeeRequiresUserTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    private Designation $designation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Acme Corp', 'code' => 'ACME',
            'email' => 'acme@example.com', 'is_active' => true,
        ]);
        $this->department = Department::create([
            'company_id' => $this->company->id, 'name' => 'Engineering',
            'code' => 'ENG', 'is_active' => true,
        ]);
        $this->designation = Designation::create(['name' => 'Engineer', 'is_active' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'is_active' => true,
        ], $overrides);
    }

    public function test_creating_an_employee_without_a_user_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/employees', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_an_unknown_user_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/employees', $this->payload(['user_id' => 999999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    /** A 422 with the custom message, never a 500 from the unique index. */
    public function test_a_user_who_is_already_an_employee_of_this_company_is_rejected(): void
    {
        $taken = User::factory()->create();

        Employee::create($this->payload(['user_id' => $taken->id]));

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/employees', $this->payload(['user_id' => $taken->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id')
            ->assertJsonPath('errors.user_id.0', 'This user is already an employee of this company.');
    }

    public function test_a_user_may_be_an_employee_in_a_second_company(): void
    {
        $person = User::factory()->create();

        Employee::create($this->payload(['user_id' => $person->id]));

        $other = Company::create(['name' => 'Globex', 'email' => 'globex@example.com']);
        $otherDepartment = Department::create([
            'company_id' => $other->id, 'name' => 'Sales',
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/employees', $this->payload([
                'user_id' => $person->id,
                'company_id' => $other->id,
                'department_id' => $otherDepartment->id,
            ]))
            ->assertCreated();

        $this->assertSame(2, $person->employees()->count());
    }

    public function test_a_department_from_another_company_is_rejected(): void
    {
        $other = Company::create(['name' => 'Globex', 'email' => 'globex@example.com']);
        $otherDepartment = Department::create([
            'company_id' => $other->id, 'name' => 'Sales',
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/employees', $this->payload([
                'user_id' => User::factory()->create()->id,
                'department_id' => $otherDepartment->id,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('department_id');
    }

    /** An edit keeps its own user without tripping the unique rule. */
    public function test_an_update_may_keep_its_current_user(): void
    {
        $owner = User::factory()->create();
        $employee = Employee::create($this->payload(['user_id' => $owner->id]));

        $this->actingAs(User::factory()->create())
            ->putJson("/v1/admin/employees/{$employee->id}", $this->payload([
                'user_id' => $owner->id,
                'description' => 'Updated.',
            ]))
            ->assertOk();

        $this->assertSame('Updated.', $employee->fresh()->description);
    }

    /**
     * The regression this whole change removes: `employees.name` used to be a
     * second copy that went stale until someone re-saved the employee.
     */
    public function test_the_employee_identity_tracks_the_user_after_a_rename(): void
    {
        $owner = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'phone' => '01711111111',
        ]);
        $employee = Employee::create($this->payload(['user_id' => $owner->id]));

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->getJson("/v1/admin/employees/{$employee->id}")
            ->assertOk()
            ->assertJsonPath('result.name', 'Old Name');

        $owner->forceFill([
            'name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '01822222222',
        ])->save();

        // No re-save of the employee — the list and the show endpoint both
        // have to pick the change up on their own.
        $this->actingAs($admin)
            ->getJson("/v1/admin/employees/{$employee->id}")
            ->assertOk()
            ->assertJsonPath('result.name', 'New Name')
            ->assertJsonPath('result.email', 'new@example.com')
            ->assertJsonPath('result.phone', '01822222222');

        $this->actingAs($admin)
            ->getJson('/v1/admin/employees')
            ->assertOk()
            ->assertJsonPath('result.data.0.name', 'New Name')
            ->assertJsonPath('result.data.0.email', 'new@example.com');
    }

    public function test_the_list_searches_and_sorts_through_the_user(): void
    {
        foreach (['Zulu Zebra', 'Alpha Ant'] as $name) {
            $slug = strtolower(str_replace(' ', '', $name));
            Employee::create($this->payload([
                'user_id' => User::factory()->create([
                    'name' => $name,
                    'email' => "{$slug}@example.com",
                ])->id,
            ]));
        }

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->getJson('/v1/admin/employees?search=Zulu')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Zulu Zebra');

        $this->actingAs($admin)
            ->getJson('/v1/admin/employees?sort=name&direction=asc')
            ->assertOk()
            ->assertJsonPath('result.data.0.name', 'Alpha Ant');

        $this->actingAs($admin)
            ->getJson('/v1/admin/employees?sort=email&direction=desc')
            ->assertOk()
            ->assertJsonPath('result.data.0.email', 'zuluzebra@example.com');
    }
}
