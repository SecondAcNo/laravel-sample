<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_categories(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create([
            'name' => 'Network',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.categories.index'));

        $response
            ->assertOk()
            ->assertSee('カテゴリ管理')
            ->assertSee($category->name);
    }

    public function test_admin_can_search_and_filter_categories(): void
    {
        $admin = User::factory()->admin()->create();
        $matchingCategory = Category::factory()->create([
            'name' => 'Network Access',
            'description' => 'VPN and internal network requests.',
            'is_active' => true,
        ]);
        Category::factory()->create([
            'name' => 'Network Archive',
            'description' => 'Old network category.',
            'is_active' => false,
        ]);
        Category::factory()->create([
            'name' => 'Device Support',
            'description' => 'PC and phone requests.',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.categories.index', [
                'q' => 'Network',
                'is_active' => '1',
            ]));

        $response
            ->assertOk()
            ->assertSee($matchingCategory->name)
            ->assertDontSee('Network Archive')
            ->assertDontSee('Device Support');
    }

    public function test_employee_cannot_view_categories(): void
    {
        $employee = User::factory()->create();

        $response = $this
            ->actingAs($employee)
            ->get(route('admin.categories.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Security',
                'description' => 'Security access and incident requests.',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.categories.index', absolute: false));
        $this->assertDatabaseHas('categories', [
            'name' => 'Security',
            'description' => 'Security access and incident requests.',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'category created',
        ]);
    }

    public function test_employee_cannot_create_category(): void
    {
        $employee = User::factory()->create();

        $response = $this
            ->actingAs($employee)
            ->post(route('admin.categories.store'), [
                'name' => 'Blocked',
                'is_active' => '1',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('categories', [
            'name' => 'Blocked',
        ]);
    }

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create([
            'name' => 'Old Name',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.categories.update', $category), [
                'name' => 'New Name',
                'description' => 'Updated category description.',
                'is_active' => '0',
            ]);

        $response->assertRedirect(route('admin.categories.index', absolute: false));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Name',
            'description' => 'Updated category description.',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'category updated',
            'target_type' => Category::class,
            'target_id' => $category->id,
        ]);
    }

    public function test_category_name_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create([
            'name' => 'Network',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), [
                'name' => 'Network',
                'is_active' => '1',
            ]);

        $response
            ->assertRedirect(route('admin.categories.create', absolute: false))
            ->assertSessionHasErrors('name');
    }
}
