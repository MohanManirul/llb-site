<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The form's Name / Email / Phone boxes are disabled, so a well-behaved browser
 * never sends them — but nothing stops a hand-crafted request. They must be
 * ignored outright: not persisted, not written through to the user, and not an
 * error either.
 */
class EmployeeIgnoresIdentityInputTest extends TestCase
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

    public function test_a_create_ignores_name_email_and_phone_in_the_body(): void
    {
        $owner = User::factory()->create([
            'name' => 'Real Name',
            'email' => 'real@example.com',
            'phone' => '01711111111',
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/employees', $this->payload([
                'user_id' => $owner->id,
                'name' => 'Injected Name',
                'email' => 'injected@example.com',
                'phone' => '01999999999',
                'password' => 'hunter2000',
            ]))
            ->assertCreated()
            // The response reads identity off the user, not off the body.
            ->assertJsonPath('result.name', 'Real Name')
            ->assertJsonPath('result.email', 'real@example.com')
            ->assertJsonPath('result.phone', '01711111111');

        $owner->refresh();
        $this->assertSame('Real Name', $owner->name);
        $this->assertSame('real@example.com', $owner->email);
        $this->assertSame('01711111111', $owner->phone);
    }

    public function test_an_update_ignores_name_email_and_phone_in_the_body(): void
    {
        $owner = User::factory()->create([
            'name' => 'Real Name',
            'email' => 'real@example.com',
            'phone' => '01711111111',
        ]);
        $employee = Employee::create($this->payload(['user_id' => $owner->id]));

        $this->actingAs(User::factory()->create())
            ->putJson("/v1/admin/employees/{$employee->id}", $this->payload([
                'user_id' => $owner->id,
                'name' => 'Injected Name',
                'email' => 'injected@example.com',
                'phone' => '01999999999',
            ]))
            ->assertOk()
            ->assertJsonPath('result.name', 'Real Name')
            ->assertJsonPath('result.email', 'real@example.com');

        $owner->refresh();
        $this->assertSame('Real Name', $owner->name);
        $this->assertSame('real@example.com', $owner->email);
        $this->assertSame('01711111111', $owner->phone);
    }

    /** No employee row may carry its own copy of the identity any more. */
    public function test_the_employees_table_has_no_identity_columns(): void
    {
        foreach (['name', 'email', 'phone', 'image'] as $column) {
            $this->assertFalse(
                Schema::hasColumn('employees', $column),
                "employees.{$column} still exists — identity must live on users.",
            );
        }
    }
}
