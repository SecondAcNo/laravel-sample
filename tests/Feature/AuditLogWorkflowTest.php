<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_creation_creates_audit_log(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('tickets.store'), [
                'title' => 'Need access',
                'description' => 'Please grant access to the reporting system.',
                'type' => Ticket::TYPE_ACCESS_REQUEST,
                'priority' => Ticket::PRIORITY_NORMAL,
                'category_id' => $category->id,
            ]);

        $ticket = Ticket::query()->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'ticket created',
            'target_type' => Ticket::class,
            'target_id' => $ticket->id,
        ]);
    }

    public function test_comment_creation_creates_audit_log(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $user->id,
        ]);

        $this
            ->actingAs($user)
            ->post(route('tickets.comments.store', $ticket), [
                'body' => 'Adding more details.',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'comment added',
        ]);
    }

    public function test_status_change_creates_audit_log(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create([
            'status' => Ticket::STATUS_OPEN,
        ]);

        $this
            ->actingAs($agent)
            ->patch(route('agent.tickets.status.update', $ticket), [
                'status' => Ticket::STATUS_TRIAGED,
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $agent->id,
            'action' => 'status changed',
            'target_type' => Ticket::class,
            'target_id' => $ticket->id,
        ]);
    }

    public function test_admin_can_view_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create([
            'name' => 'Network',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.audit-logs.index'));

        $response
            ->assertOk()
            ->assertSee('監査ログ');
    }

    public function test_admin_can_filter_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create([
            'name' => 'Filter Target User',
            'email' => 'filter-target@example.test',
        ]);
        AuditLog::create([
            'user_id' => $employee->id,
            'action' => 'ticket updated',
            'target_type' => Ticket::class,
            'target_id' => 10,
        ]);
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'category updated',
            'target_type' => Category::class,
            'target_id' => 20,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.audit-logs.index', [
                'q' => 'Filter Target',
                'action' => 'ticket updated',
                'target_type' => Ticket::class,
            ]));

        $response
            ->assertOk()
            ->assertSee('ticket updated')
            ->assertSee('Filter Target User')
            ->assertDontSee('Category #20');
    }

    public function test_employee_cannot_view_audit_logs(): void
    {
        $employee = User::factory()->create();

        $response = $this
            ->actingAs($employee)
            ->get(route('admin.audit-logs.index'));

        $response->assertForbidden();
    }
}
