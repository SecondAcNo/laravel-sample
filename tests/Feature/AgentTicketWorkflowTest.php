<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_agent_can_view_agent_dashboard(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $assignedTicket = Ticket::factory()->assignedTo($agent)->create([
            'title' => 'Assigned dashboard ticket',
        ]);
        $unassignedTicket = Ticket::factory()->create([
            'title' => 'Unassigned dashboard ticket',
            'assignee_id' => null,
        ]);
        $otherAgent = User::factory()->supportAgent()->create();
        Ticket::factory()->assignedTo($otherAgent)->create([
            'title' => 'Other agent dashboard ticket',
        ]);

        $response = $this
            ->actingAs($agent)
            ->get(route('agent.dashboard'));

        $response
            ->assertOk()
            ->assertSee('担当者ダッシュボード')
            ->assertSee('最近の担当チケット')
            ->assertSee('最近の未割当チケット')
            ->assertSee($assignedTicket->title)
            ->assertSee($unassignedTicket->title)
            ->assertDontSee('Other agent dashboard ticket');
    }

    public function test_employee_cannot_view_agent_dashboard(): void
    {
        $employee = User::factory()->create();

        $response = $this
            ->actingAs($employee)
            ->get(route('agent.dashboard'));

        $response->assertForbidden();
    }

    public function test_support_agent_can_view_assigned_ticket(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();

        $response = $this
            ->actingAs($agent)
            ->get(route('agent.tickets.show', $ticket));

        $response
            ->assertOk()
            ->assertSee($ticket->ticket_no)
            ->assertSee($ticket->title);
    }

    public function test_support_agent_can_view_unassigned_ticket_detail(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->create([
            'assignee_id' => null,
        ]);

        $response = $this
            ->actingAs($agent)
            ->get(route('agent.tickets.show', $ticket));

        $response
            ->assertOk()
            ->assertSee($ticket->ticket_no)
            ->assertSee('Claim Ticket')
            ->assertDontSee('Post Comment')
            ->assertDontSee('Upload');
    }

    public function test_support_agent_index_lists_assigned_and_unassigned_tickets(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $otherAgent = User::factory()->supportAgent()->create();
        $assignedTicket = Ticket::factory()->assignedTo($agent)->create([
            'title' => 'Assigned ticket',
        ]);
        $unassignedTicket = Ticket::factory()->create([
            'title' => 'Unassigned ticket',
            'assignee_id' => null,
        ]);
        Ticket::factory()->assignedTo($otherAgent)->create([
            'title' => 'Other assigned ticket',
        ]);

        $response = $this
            ->actingAs($agent)
            ->get(route('agent.tickets.index'));

        $response
            ->assertOk()
            ->assertSee($assignedTicket->title)
            ->assertSee($unassignedTicket->title)
            ->assertDontSee('Other assigned ticket');
    }

    public function test_support_agent_can_search_visible_tickets(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $otherAgent = User::factory()->supportAgent()->create();
        $matchingTicket = Ticket::factory()->assignedTo($agent)->create([
            'title' => 'VPN access issue',
            'status' => Ticket::STATUS_OPEN,
        ]);
        Ticket::factory()->assignedTo($otherAgent)->create([
            'title' => 'VPN issue owned by another agent',
            'status' => Ticket::STATUS_OPEN,
        ]);
        Ticket::factory()->create([
            'title' => 'Laptop issue',
            'status' => Ticket::STATUS_RESOLVED,
            'assignee_id' => null,
        ]);

        $response = $this
            ->actingAs($agent)
            ->get(route('agent.tickets.index', [
                'q' => 'VPN',
                'status' => Ticket::STATUS_OPEN,
            ]));

        $response
            ->assertOk()
            ->assertSee($matchingTicket->title)
            ->assertDontSee('VPN issue owned by another agent')
            ->assertDontSee('Laptop issue');
    }

    public function test_support_agent_can_filter_unassigned_queue(): void
    {
        $agent = User::factory()->supportAgent()->create();
        Ticket::factory()->assignedTo($agent)->create([
            'title' => 'Assigned queue ticket',
        ]);
        $unassignedTicket = Ticket::factory()->create([
            'title' => 'Unassigned queue ticket',
            'assignee_id' => null,
        ]);

        $response = $this
            ->actingAs($agent)
            ->get(route('agent.tickets.index', [
                'assignee' => 'unassigned',
            ]));

        $response
            ->assertOk()
            ->assertSee($unassignedTicket->title)
            ->assertDontSee('Assigned queue ticket');
    }

    public function test_support_agent_can_claim_unassigned_ticket(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->create([
            'assignee_id' => null,
        ]);

        $response = $this
            ->actingAs($agent)
            ->patch(route('agent.tickets.claim.update', $ticket));

        $response
            ->assertRedirect(route('agent.tickets.show', $ticket, absolute: false))
            ->assertSessionHas('status', 'Ticket claimed.');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => $agent->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $agent->id,
            'action' => 'ticket assignee changed',
            'target_type' => Ticket::class,
            'target_id' => $ticket->id,
        ]);

        $auditLog = AuditLog::query()->where('target_id', $ticket->id)->firstOrFail();

        $this->assertSame(['assignee_id' => null], $auditLog->before_values);
        $this->assertSame(['assignee_id' => $agent->id], $auditLog->after_values);
    }

    public function test_support_agent_cannot_claim_already_assigned_ticket(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $otherAgent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->assignedTo($otherAgent)->create();

        $response = $this
            ->actingAs($agent)
            ->patch(route('agent.tickets.claim.update', $ticket));

        $response->assertForbidden();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => $otherAgent->id,
        ]);
    }

    public function test_support_agent_can_comment_on_assigned_ticket(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();

        $response = $this
            ->actingAs($agent)
            ->post(route('tickets.comments.store', $ticket), [
                'body' => 'I am checking this request.',
            ]);

        $response->assertRedirect(route('agent.tickets.show', $ticket, absolute: false));
        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'body' => 'I am checking this request.',
        ]);
    }

    public function test_support_agent_cannot_comment_on_unassigned_ticket(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->create([
            'assignee_id' => null,
        ]);

        $response = $this
            ->actingAs($agent)
            ->post(route('tickets.comments.store', $ticket), [
                'body' => 'This should not be accepted.',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('ticket_comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
        ]);
    }

    public function test_support_agent_can_update_assigned_ticket_status_with_allowed_transition(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create([
            'status' => Ticket::STATUS_OPEN,
        ]);

        $response = $this
            ->actingAs($agent)
            ->patch(route('agent.tickets.status.update', $ticket), [
                'status' => Ticket::STATUS_TRIAGED,
            ]);

        $response->assertRedirect(route('agent.tickets.show', $ticket, absolute: false));
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_TRIAGED,
        ]);
    }

    public function test_support_agent_cannot_skip_ticket_status_transition(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create([
            'status' => Ticket::STATUS_OPEN,
        ]);

        $response = $this
            ->actingAs($agent)
            ->from(route('agent.tickets.show', $ticket))
            ->patch(route('agent.tickets.status.update', $ticket), [
                'status' => Ticket::STATUS_RESOLVED,
            ]);

        $response
            ->assertRedirect(route('agent.tickets.show', $ticket, absolute: false))
            ->assertSessionHasErrors('status');
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
    }

    public function test_support_agent_cannot_update_unassigned_ticket_status(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->create([
            'assignee_id' => null,
            'status' => Ticket::STATUS_OPEN,
        ]);

        $response = $this
            ->actingAs($agent)
            ->patch(route('agent.tickets.status.update', $ticket), [
                'status' => Ticket::STATUS_TRIAGED,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
    }

    public function test_employee_cannot_update_ticket_status_through_agent_route(): void
    {
        $employee = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'status' => Ticket::STATUS_OPEN,
        ]);

        $response = $this
            ->actingAs($employee)
            ->patch(route('agent.tickets.status.update', $ticket), [
                'status' => Ticket::STATUS_TRIAGED,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
    }
}
