<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers what Employees/Index.jsx relies on now that the page carries no
 * server props: the API has to do the searching, filtering, sorting and
 * paginating that the old Inertia controller did — and it has to hand back
 * `image` on every row, because the view modal renders the picture straight
 * off the clicked row.
 */
class ApiEmployeeIndexTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    private Designation $designation;

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

        $this->designation = $this->makeDesignation('Engineer');
    }

    private function makeDesignation(string $name): Designation
    {
        return Designation::create(['name' => $name, 'is_active' => true]);
    }

    /**
     * Identity lives on `users` now, so an employee fixture always needs a user
     * to hang its name / email / phone / photo off.
     */
    private function makeEmployee(string $name, array $attributes = []): Employee
    {
        $slug = str_replace(' ', '', strtolower($name));

        $user = User::factory()->create([
            'name' => $name,
            'email' => "{$slug}@example.com",
            'phone' => $attributes['phone'] ?? null,
            'image' => $attributes['image'] ?? null,
        ]);

        unset($attributes['phone'], $attributes['image']);

        return Employee::create(array_merge([
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'is_active' => true,
        ], $attributes));
    }

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/v1/admin/employees')->assertUnauthorized();
    }

    public function test_it_returns_a_paginator_the_datatable_can_render(): void
    {
        $this->makeEmployee('Alice');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/employees')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'result' => [
                    'data' => [[
                        'id', 'user_id', 'company_id', 'company_name', 'department_id',
                        'department_name', 'designation_id', 'designation', 'name',
                        'email',
                        'phone', 'description', 'image_url', 'thumbnail_url', 'joining_date',
                        'resignation_date', 'is_active', 'created_at',
                    ]],
                    'links' => ['prev', 'next'],
                    'meta' => ['current_page', 'from', 'to', 'per_page'],
                ],
            ]);
    }

    /** Without the image urls on the row the view modal has no picture to show. */
    public function test_a_row_carries_the_image_urls(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('users/alice.jpg', 'fake-bytes');

        $this->makeEmployee('Alice', ['image' => 'users/alice.jpg']);

        $url = Storage::disk('public')->url('users/alice.jpg');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/employees')
            ->assertOk()
            ->assertJsonPath('result.data.0.image_url', $url)
            ->assertJsonPath('result.data.0.thumbnail_url', $url);
    }

    public function test_it_searches_across_the_employee_and_its_relations(): void
    {
        $this->makeEmployee('Alice');
        $this->makeEmployee('Bob');

        $user = User::factory()->create();

        // Own column.
        $this->actingAs($user)
            ->getJson('/v1/admin/employees?search=Alice')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Alice');

        // Related company name — both employees belong to Acme Corp.
        $this->actingAs($user)
            ->getJson('/v1/admin/employees?search=Acme')
            ->assertOk()
            ->assertJsonCount(2, 'result.data');
    }

    public function test_it_filters_by_status(): void
    {
        $this->makeEmployee('Active Andy');
        $this->makeEmployee('Idle Ida', ['is_active' => false]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/v1/admin/employees?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Idle Ida');

        $this->actingAs($user)
            ->getJson('/v1/admin/employees?is_active=1')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Active Andy');
    }

    public function test_it_filters_by_company_and_department(): void
    {
        $other = Company::create([
            'name' => 'Globex', 'code' => 'GLX',
            'email' => 'globex@example.com', 'is_active' => true,
        ]);
        $otherDepartment = Department::create([
            'company_id' => $other->id, 'name' => 'Sales',
            'code' => 'SAL', 'is_active' => true,
        ]);

        $this->makeEmployee('Alice');
        $this->makeEmployee('Bob', [
            'company_id' => $other->id,
            'department_id' => $otherDepartment->id,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson("/v1/admin/employees?company_id={$other->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Bob');

        $this->actingAs($user)
            ->getJson("/v1/admin/employees?department_id={$this->department->id}")
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.name', 'Alice');
    }

    public function test_it_sorts_by_a_whitelisted_column(): void
    {
        $this->makeEmployee('Zulu');
        $this->makeEmployee('Alpha');

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/employees?sort=name&direction=asc')
            ->assertOk()
            ->assertJsonPath('result.data.0.name', 'Alpha');
    }

    public function test_it_honours_per_page(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->makeEmployee("Employee {$i}");
        }

        $this->actingAs(User::factory()->create())
            ->getJson('/v1/admin/employees?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'result.data')
            ->assertJsonPath('result.meta.per_page', 2)
            ->assertJsonPath('result.meta.current_page', 1);
    }

    public function test_it_soft_deletes_an_employee(): void
    {
        $keep = $this->makeEmployee('Keep');
        $drop = $this->makeEmployee('Drop One');

        $this->actingAs(User::factory()->create())
            ->deleteJson("/v1/admin/employees/{$drop->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('employees', ['id' => $drop->id]);
        $this->assertNotSoftDeleted('employees', ['id' => $keep->id]);
    }

    /** The photo belongs to the user, so deleting the employee never touches it. */
    public function test_a_delete_leaves_the_uploaded_image_on_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('users/alice.jpg', 'fake-bytes');

        $employee = $this->makeEmployee('Alice', ['image' => 'users/alice.jpg']);

        $this->actingAs(User::factory()->create())
            ->deleteJson("/v1/admin/employees/{$employee->id}")
            ->assertOk();

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
        Storage::disk('public')->assertExists('users/alice.jpg');
        $this->assertSame('users/alice.jpg', $employee->user->fresh()->image);
    }

    /** The team-member picker's option list. */
    public function test_it_searches_employees_for_the_member_picker(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('users/alice.jpg', 'fake-bytes');

        $this->makeEmployee('Alice', ['image' => 'users/alice.jpg']);
        $this->makeEmployee('Bob');

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/employees/search?company_id={$this->company->id}&search=Ali")
            ->assertOk()
            ->assertJsonCount(1, 'result')
            ->assertJsonPath('result.0.label', 'Alice')
            ->assertJsonPath('result.0.thumbnail_url', Storage::disk('public')->url('users/alice.jpg'));
    }

    /**
     * The edit form hydrates its designation select from the id and shows the
     * name, so the show response has to carry both.
     */
    public function test_the_show_endpoint_returns_the_image_and_both_designation_fields(): void
    {
        $manager = $this->makeDesignation('Manager');

        Storage::fake('public');
        Storage::disk('public')->put('users/alice.jpg', 'fake-bytes');

        $employee = $this->makeEmployee('Alice', [
            'image' => 'users/alice.jpg',
            'designation_id' => $manager->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson("/v1/admin/employees/{$employee->id}")
            ->assertOk()
            ->assertJsonPath('result.image_url', Storage::disk('public')->url('users/alice.jpg'))
            ->assertJsonPath('result.company_name', 'Acme Corp')
            ->assertJsonPath('result.designation_id', $manager->id)
            ->assertJsonPath('result.designation', 'Manager');
    }

    /**
     * The form carries no file any more, so the update is plain JSON — no
     * multipart, no _method spoofing.
     */
    public function test_an_update_repoints_the_designation(): void
    {
        $manager = $this->makeDesignation('Manager');
        $employee = $this->makeEmployee('Alice');

        $this->actingAs(User::factory()->create())
            ->putJson("/v1/admin/employees/{$employee->id}", [
                'user_id' => $employee->user_id,
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'designation_id' => $manager->id,
                // The form always sends every field, so the optional ones
                // arrive empty — they must still validate.
                'description' => '',
                'joining_date' => null,
                'resignation_date' => null,
                'is_active' => '1',
            ])
            ->assertOk();

        $employee->refresh();

        $this->assertSame($manager->id, $employee->designation_id);
        $this->assertSame('Manager', $employee->designation->name);
    }

    /** An image in the body is not an employee field at all — it is ignored. */
    public function test_an_update_does_not_accept_an_image(): void
    {
        Storage::fake('public');

        $employee = $this->makeEmployee('Alice');

        $this->actingAs(User::factory()->create())
            ->post("/v1/admin/employees/{$employee->id}", [
                '_method' => 'put',
                'user_id' => $employee->user_id,
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'designation_id' => $this->designation->id,
                'is_active' => '1',
                'image' => UploadedFile::fake()->create('alice.jpg', 10),
            ])
            ->assertOk();

        $this->assertNull($employee->fresh()->image);
        $this->assertNull($employee->user->fresh()->image);
    }

    /** A designation the employee form invented has nothing to point at. */
    public function test_an_update_rejects_an_unknown_designation(): void
    {
        $employee = $this->makeEmployee('Alice');

        $this->actingAs(User::factory()->create())
            ->putJson("/v1/admin/employees/{$employee->id}", [
                'user_id' => $employee->user_id,
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'designation_id' => 9999,
                'is_active' => '1',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('designation_id');
    }

    /** A resignation date settles the status: the employee is inactive whatever the form sent. */
    public function test_a_resignation_date_forces_the_employee_inactive(): void
    {
        $employee = $this->makeEmployee('Alice');

        $this->actingAs(User::factory()->create())
            ->putJson("/v1/admin/employees/{$employee->id}", [
                'user_id' => $employee->user_id,
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'designation_id' => $this->designation->id,
                'joining_date' => null,
                'resignation_date' => '2026-08-25',
                'is_active' => '1',
            ])
            ->assertOk()
            ->assertJsonPath('result.is_active', false);

        $this->assertFalse($employee->fresh()->is_active);
    }

    /** A resignation date in the future counts the same — no scheduled flip to wait for. */
    public function test_a_future_resignation_date_forces_the_employee_inactive(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/v1/admin/employees', [
                'user_id' => User::factory()->create()->id,
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'designation_id' => $this->designation->id,
                'joining_date' => null,
                'resignation_date' => now()->addMonth()->toDateString(),
                'is_active' => '1',
            ])
            ->assertCreated()
            ->assertJsonPath('result.is_active', false);
    }

    /** Without a resignation date the submitted status still decides. */
    public function test_no_resignation_date_leaves_the_submitted_status_alone(): void
    {
        $employee = $this->makeEmployee('Alice', ['is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->putJson("/v1/admin/employees/{$employee->id}", [
                'user_id' => $employee->user_id,
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'designation_id' => $this->designation->id,
                'joining_date' => null,
                'resignation_date' => null,
                'is_active' => '1',
            ])
            ->assertOk()
            ->assertJsonPath('result.is_active', true);
    }
}
