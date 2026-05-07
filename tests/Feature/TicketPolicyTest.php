<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\Category;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_view_and_comment_on_own_ticket_only(): void
    {
        $employee = User::factory()->create();
        $ownTicket = Ticket::factory()->create([
            'requester_id' => $employee->id,
        ]);
        $otherTicket = Ticket::factory()->create();

        $this->assertTrue($employee->can('view', $ownTicket));
        $this->assertTrue($employee->can('comment', $ownTicket));
        $this->assertTrue($employee->can('attach', $ownTicket));
        $this->assertTrue($employee->can('update', $ownTicket));
        $this->assertFalse($employee->can('view', $otherTicket));
        $this->assertFalse($employee->can('comment', $otherTicket));
        $this->assertFalse($employee->can('attach', $otherTicket));
        $this->assertFalse($employee->can('update', $otherTicket));
    }

    public function test_support_agent_can_view_assigned_ticket_only(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $assignedTicket = Ticket::factory()->assignedTo($agent)->create();
        $unassignedTicket = Ticket::factory()->create([
            'assignee_id' => null,
        ]);

        $this->assertTrue($agent->can('viewAssigned', $assignedTicket));
        $this->assertTrue($agent->can('comment', $assignedTicket));
        $this->assertTrue($agent->can('attach', $assignedTicket));
        $this->assertTrue($agent->can('updateStatus', $assignedTicket));
        $this->assertFalse($agent->can('update', $assignedTicket));
        $this->assertTrue($agent->can('viewAssigned', $unassignedTicket));
        $this->assertTrue($agent->can('claim', $unassignedTicket));
        $this->assertTrue($agent->can('downloadAttachment', $unassignedTicket));
        $this->assertFalse($agent->can('comment', $unassignedTicket));
        $this->assertFalse($agent->can('attach', $unassignedTicket));
        $this->assertFalse($agent->can('updateStatus', $unassignedTicket));
    }

    public function test_admin_can_view_and_update_any_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();

        $this->assertTrue($admin->can('viewAny', Ticket::class));
        $this->assertTrue($admin->can('view', $ticket));
        $this->assertTrue($admin->can('viewAssigned', $ticket));
        $this->assertTrue($admin->can('comment', $ticket));
        $this->assertTrue($admin->can('update', $ticket));
        $this->assertTrue($admin->can('updateStatus', $ticket));
        $this->assertTrue($admin->can('assign', $ticket));
    }

    public function test_employee_can_update_own_open_ticket_only(): void
    {
        $employee = User::factory()->create();
        $openTicket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
        $triagedTicket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'status' => Ticket::STATUS_TRIAGED,
        ]);

        $this->assertTrue($employee->can('update', $openTicket));
        $this->assertFalse($employee->can('update', $triagedTicket));
    }

    public function test_only_admin_can_assign_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create();
        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->create();

        $this->assertTrue($admin->can('assign', $ticket));
        $this->assertFalse($employee->can('assign', $ticket));
        $this->assertFalse($agent->can('assign', $ticket));
    }

    public function test_only_admin_can_manage_users(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create();
        $agent = User::factory()->supportAgent()->create();

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('create', User::class));
        $this->assertFalse($employee->can('viewAny', User::class));
        $this->assertFalse($employee->can('create', User::class));
        $this->assertFalse($agent->can('viewAny', User::class));
        $this->assertFalse($agent->can('create', User::class));
    }

    public function test_only_admin_can_manage_categories(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create();
        $agent = User::factory()->supportAgent()->create();
        $category = Category::factory()->create();

        $this->assertTrue($admin->can('viewAny', Category::class));
        $this->assertTrue($admin->can('create', Category::class));
        $this->assertTrue($admin->can('update', $category));
        $this->assertFalse($employee->can('viewAny', Category::class));
        $this->assertFalse($employee->can('create', Category::class));
        $this->assertFalse($employee->can('update', $category));
        $this->assertFalse($agent->can('viewAny', Category::class));
        $this->assertFalse($agent->can('create', Category::class));
        $this->assertFalse($agent->can('update', $category));
    }

    public function test_only_admin_can_view_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create();
        $agent = User::factory()->supportAgent()->create();

        $this->assertTrue($admin->can('viewAny', AuditLog::class));
        $this->assertFalse($employee->can('viewAny', AuditLog::class));
        $this->assertFalse($agent->can('viewAny', AuditLog::class));
    }
}
