<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeededUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_roles_and_departments_are_seeded(): void
    {
        $this->seed();

        $this->assertDatabaseHas('departments', [
            'name' => 'IT',
        ]);

        $this->assertDatabaseHas('departments', [
            'name' => 'Sales',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.test',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'agent@example.test',
            'role' => 'support_agent',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'employee@example.test',
            'role' => 'employee',
        ]);
    }

    public function test_user_belongs_to_department(): void
    {
        $department = Department::create([
            'name' => 'IT',
        ]);

        $user = User::factory()->create([
            'department_id' => $department->id,
        ]);

        $this->assertTrue($user->department->is($department));
    }
}
