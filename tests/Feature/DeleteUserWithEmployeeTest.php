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
 * `employees.user_id` is RESTRICT, so deleting an employed user would throw a
 * raw QueryException mid-request. The controller has to catch that first.
 */
class DeleteUserWithEmployeeTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(User $user): Employee
    {
        $company = Company::create([
            'name' => 'Acme Corp', 'code' => 'ACME',
            'email' => 'acme@example.com', 'is_active' => true,
        ]);
        $department = Department::create([
            'company_id' => $company->id, 'name' => 'Engineering',
            'code' => 'ENG', 'is_active' => true,
        ]);

        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'designation_id' => Designation::create(['name' => 'Engineer', 'is_active' => true])->id,
            'is_active' => true,
        ]);
    }

    public function test_deleting_an_employed_user_returns_a_422_not_a_500(): void
    {
        $employed = User::factory()->create();
        $this->makeEmployee($employed);

        $this->actingAs(User::factory()->create())
            ->deleteJson("/v1/admin/users/{$employed->id}")
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'This user is an employee. Delete the employee records first.',
            );

        $this->assertDatabaseHas('users', ['id' => $employed->id]);
    }

    public function test_a_user_with_no_employee_record_still_deletes(): void
    {
        $plain = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->deleteJson("/v1/admin/users/{$plain->id}")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $plain->id]);
    }

    /**
     * Deleting an employee only soft-deletes it, so the row — and its FK
     * reference — survives. The guard has to stay up, or the RESTRICT fires.
     */
    public function test_a_soft_deleted_employee_still_blocks_the_delete(): void
    {
        $employed = User::factory()->create();
        $employee = $this->makeEmployee($employed);

        $employee->delete();
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);

        $this->actingAs(User::factory()->create())
            ->deleteJson("/v1/admin/users/{$employed->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $employed->id]);
    }

    public function test_the_guard_lifts_once_the_employee_is_hard_deleted(): void
    {
        $employed = User::factory()->create();
        $employee = $this->makeEmployee($employed);

        $employee->forceDelete();

        $this->actingAs(User::factory()->create())
            ->deleteJson("/v1/admin/users/{$employed->id}")
            ->assertOk();
    }
}
