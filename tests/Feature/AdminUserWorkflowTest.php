<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_users(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create([
            'name' => 'Employee User',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.users.index'));

        $response
            ->assertOk()
            ->assertSee('ユーザー管理')
            ->assertSee($employee->name);
    }

    public function test_admin_can_search_and_filter_users(): void
    {
        $admin = User::factory()->admin()->create();
        $it = Department::create([
            'name' => 'IT',
        ]);
        $hr = Department::create([
            'name' => 'HR',
        ]);
        $matchingUser = User::factory()->supportAgent()->create([
            'name' => 'Searchable Agent',
            'email' => 'searchable-agent@example.test',
            'department_id' => $it->id,
        ]);
        User::factory()->supportAgent()->create([
            'name' => 'Other Agent',
            'email' => 'other-agent@example.test',
            'department_id' => $hr->id,
        ]);
        User::factory()->create([
            'name' => 'Searchable Employee',
            'email' => 'searchable-employee@example.test',
            'department_id' => $it->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.users.index', [
                'q' => 'Searchable',
                'role' => 'support_agent',
                'department_id' => $it->id,
            ]));

        $response
            ->assertOk()
            ->assertSee($matchingUser->name)
            ->assertDontSee('Other Agent')
            ->assertDontSee('Searchable Employee');
    }

    public function test_employee_cannot_view_users(): void
    {
        $employee = User::factory()->create();

        $response = $this
            ->actingAs($employee)
            ->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->admin()->create();
        $department = Department::create([
            'name' => 'IT',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Agent',
                'email' => 'new-agent@example.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'support_agent',
                'department_id' => $department->id,
            ]);

        $response->assertRedirect(route('admin.users.index', absolute: false));
        $this->assertDatabaseHas('users', [
            'name' => 'New Agent',
            'email' => 'new-agent@example.test',
            'role' => 'support_agent',
            'department_id' => $department->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'user created',
        ]);
    }

    public function test_employee_cannot_create_user(): void
    {
        $employee = User::factory()->create();

        $response = $this
            ->actingAs($employee)
            ->post(route('admin.users.store'), [
                'name' => 'Blocked User',
                'email' => 'blocked@example.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'employee',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', [
            'email' => 'blocked@example.test',
        ]);
    }

    public function test_admin_user_creation_requires_valid_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Invalid Role',
                'email' => 'invalid-role@example.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'owner',
            ]);

        $response
            ->assertRedirect(route('admin.users.create', absolute: false))
            ->assertSessionHasErrors('role');
    }

    public function test_admin_can_update_user_role_and_department(): void
    {
        $admin = User::factory()->admin()->create();
        $department = Department::create([
            'name' => 'IT Support',
        ]);
        $user = User::factory()->create([
            'name' => 'Help Desk Member',
            'email' => 'member@example.test',
            'role' => 'employee',
            'department_id' => null,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => 'Help Desk Agent',
                'email' => 'agent-member@example.test',
                'role' => 'support_agent',
                'department_id' => $department->id,
            ]);

        $response
            ->assertRedirect(route('admin.users.index', absolute: false))
            ->assertSessionHas('status', 'User updated.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Help Desk Agent',
            'email' => 'agent-member@example.test',
            'role' => 'support_agent',
            'department_id' => $department->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'user role changed',
            'target_type' => User::class,
            'target_id' => $user->id,
        ]);

        $auditLog = AuditLog::query()->where('target_id', $user->id)->firstOrFail();

        $this->assertSame('employee', $auditLog->before_values['role']);
        $this->assertSame('support_agent', $auditLog->after_values['role']);
    }

    public function test_admin_can_update_user_without_changing_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.test',
            'role' => 'employee',
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => 'Updated Name',
                'email' => 'updated@example.test',
                'role' => 'employee',
                'department_id' => '',
            ]);

        $response->assertRedirect(route('admin.users.index', absolute: false));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.test',
            'role' => 'employee',
            'department_id' => null,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'user updated',
            'target_type' => User::class,
            'target_id' => $user->id,
        ]);
    }

    public function test_employee_cannot_update_user(): void
    {
        $employee = User::factory()->create();
        $user = User::factory()->supportAgent()->create([
            'role' => 'support_agent',
        ]);

        $response = $this
            ->actingAs($employee)
            ->patch(route('admin.users.update', $user), [
                'name' => 'Blocked Update',
                'email' => 'blocked-update@example.test',
                'role' => 'admin',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'support_agent',
        ]);
    }

    public function test_admin_user_update_requires_unique_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create([
            'email' => 'used@example.test',
        ]);
        $user = User::factory()->create([
            'email' => 'editable@example.test',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.users.edit', $user))
            ->patch(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => 'used@example.test',
                'role' => $user->role,
                'department_id' => '',
            ]);

        $response
            ->assertRedirect(route('admin.users.edit', $user, absolute: false))
            ->assertSessionHasErrors('email');
    }
}
