<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_home_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_admin_home_redirects_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->get('/');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_support_agent_home_redirects_to_agent_dashboard(): void
    {
        $agent = User::factory()->supportAgent()->create();

        $response = $this
            ->actingAs($agent)
            ->get('/');

        $response->assertRedirect(route('agent.dashboard'));
    }

    public function test_employee_home_redirects_to_employee_dashboard(): void
    {
        $employee = User::factory()->create();

        $response = $this
            ->actingAs($employee)
            ->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
