<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_navigation_shows_employee_links_only(): void
    {
        $employee = User::factory()->create();

        $response = $this
            ->actingAs($employee)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('ダッシュボード')
            ->assertSee('チケット')
            ->assertDontSee('担当チケット')
            ->assertDontSee('管理ホーム')
            ->assertDontSee('監査ログ');
    }

    public function test_support_agent_navigation_shows_agent_links(): void
    {
        $agent = User::factory()->supportAgent()->create();

        $response = $this
            ->actingAs($agent)
            ->get(route('agent.dashboard'));

        $response
            ->assertOk()
            ->assertSee('担当者ホーム')
            ->assertSee('担当チケット')
            ->assertDontSee('管理ホーム')
            ->assertDontSee('監査ログ');
    }

    public function test_admin_navigation_shows_admin_links(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.dashboard'));

        $response
            ->assertOk()
            ->assertSee('管理ホーム')
            ->assertSee('全チケット')
            ->assertSee('ユーザー')
            ->assertSee('カテゴリ')
            ->assertSee('監査ログ')
            ->assertDontSee('担当チケット');
    }

    public function test_logout_control_submits_post_form_without_logout_link(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.dashboard'));

        $response
            ->assertOk()
            ->assertSee('<details', false)
            ->assertSee('<summary', false)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('type="submit"', false)
            ->assertDontSee('href="'.route('logout').'"', false);
    }
}
