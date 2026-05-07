<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_create_a_ticket(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/tickets', [
                'title' => 'VPN is not connecting',
                'description' => 'The VPN client fails after entering my credentials.',
                'type' => Ticket::TYPE_INCIDENT,
                'priority' => Ticket::PRIORITY_HIGH,
                'category_id' => $category->id,
            ]);

        $ticket = Ticket::query()->firstOrFail();

        $response->assertRedirect(route('tickets.show', $ticket, absolute: false));
        $this->assertDatabaseHas('tickets', [
            'title' => 'VPN is not connecting',
            'requester_id' => $user->id,
            'status' => Ticket::STATUS_OPEN,
            'assignee_id' => null,
        ]);
    }

    public function test_employee_can_view_own_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('tickets.show', $ticket));

        $response
            ->assertOk()
            ->assertSee($ticket->ticket_no)
            ->assertSee($ticket->title);
    }

    public function test_employee_dashboard_shows_own_ticket_summary(): void
    {
        $user = User::factory()->create();
        $ownTicket = Ticket::factory()->create([
            'requester_id' => $user->id,
            'title' => 'My recent VPN ticket',
            'status' => Ticket::STATUS_WAITING_USER,
        ]);
        Ticket::factory()->create([
            'title' => 'Other user ticket',
            'status' => Ticket::STATUS_WAITING_USER,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('未対応チケット')
            ->assertSee('確認待ち')
            ->assertSee('解決済み')
            ->assertSee($ownTicket->title)
            ->assertDontSee('Other user ticket');
    }

    public function test_employee_cannot_view_another_users_ticket(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $otherUser->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('tickets.show', $ticket));

        $response->assertForbidden();
    }

    public function test_ticket_index_only_lists_authenticated_users_tickets(): void
    {
        $user = User::factory()->create();
        $ownTicket = Ticket::factory()->create([
            'title' => 'Own ticket',
            'requester_id' => $user->id,
        ]);
        Ticket::factory()->create([
            'title' => 'Someone else ticket',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('tickets.index'));

        $response
            ->assertOk()
            ->assertSee($ownTicket->title)
            ->assertDontSee('Someone else ticket');
    }

    public function test_employee_can_search_own_tickets(): void
    {
        $user = User::factory()->create();
        $matchingTicket = Ticket::factory()->create([
            'requester_id' => $user->id,
            'title' => 'VPN connection issue',
            'priority' => Ticket::PRIORITY_URGENT,
        ]);
        Ticket::factory()->create([
            'requester_id' => $user->id,
            'title' => 'Keyboard replacement',
            'priority' => Ticket::PRIORITY_LOW,
        ]);
        Ticket::factory()->create([
            'title' => 'VPN issue from another user',
            'priority' => Ticket::PRIORITY_URGENT,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('tickets.index', [
                'q' => 'VPN',
                'priority' => Ticket::PRIORITY_URGENT,
            ]));

        $response
            ->assertOk()
            ->assertSee($matchingTicket->title)
            ->assertDontSee('Keyboard replacement')
            ->assertDontSee('VPN issue from another user');
    }

    public function test_employee_can_update_own_open_ticket(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $user->id,
            'status' => Ticket::STATUS_OPEN,
            'title' => 'Original title',
            'description' => 'Original description',
            'priority' => Ticket::PRIORITY_NORMAL,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('tickets.update', $ticket), [
                'title' => 'Updated VPN request',
                'description' => 'Updated details for the service desk.',
                'type' => Ticket::TYPE_INCIDENT,
                'priority' => Ticket::PRIORITY_HIGH,
                'category_id' => $category->id,
            ]);

        $response
            ->assertRedirect(route('tickets.show', $ticket, absolute: false))
            ->assertSessionHas('status', 'Ticket updated.');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'title' => 'Updated VPN request',
            'description' => 'Updated details for the service desk.',
            'type' => Ticket::TYPE_INCIDENT,
            'priority' => Ticket::PRIORITY_HIGH,
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'ticket updated',
            'target_type' => Ticket::class,
            'target_id' => $ticket->id,
        ]);

        $auditLog = AuditLog::query()->where('target_id', $ticket->id)->firstOrFail();

        $this->assertSame('Original title', $auditLog->before_values['title']);
        $this->assertSame('Updated VPN request', $auditLog->after_values['title']);
    }

    public function test_employee_cannot_update_another_users_ticket(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $otherUser->id,
            'status' => Ticket::STATUS_OPEN,
            'title' => 'Original title',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('tickets.update', $ticket), [
                'title' => 'Blocked update',
                'description' => 'This should not be saved.',
                'type' => Ticket::TYPE_INCIDENT,
                'priority' => Ticket::PRIORITY_HIGH,
                'category_id' => $category->id,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'title' => 'Original title',
        ]);
    }

    public function test_employee_cannot_update_own_ticket_after_triage(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $user->id,
            'status' => Ticket::STATUS_TRIAGED,
            'title' => 'Original title',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('tickets.update', $ticket), [
                'title' => 'Late update',
                'description' => 'This should not be saved after triage.',
                'type' => Ticket::TYPE_GENERAL_REQUEST,
                'priority' => Ticket::PRIORITY_NORMAL,
                'category_id' => $category->id,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'title' => 'Original title',
        ]);
    }

    public function test_employee_can_comment_on_own_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('tickets.comments.store', $ticket), [
                'body' => 'I can reproduce this issue after rebooting.',
            ]);

        $response->assertRedirect(route('tickets.show', $ticket, absolute: false));
        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'I can reproduce this issue after rebooting.',
        ]);
    }

    public function test_employee_cannot_comment_on_another_users_ticket(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $otherUser->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('tickets.comments.store', $ticket), [
                'body' => 'This should not be accepted.',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('ticket_comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_ticket_comment_body_is_required(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('tickets.show', $ticket))
            ->post(route('tickets.comments.store', $ticket), [
                'body' => '',
            ]);

        $response
            ->assertRedirect(route('tickets.show', $ticket, absolute: false))
            ->assertSessionHasErrors('body');
    }

    public function test_employee_can_close_own_resolved_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $user->id,
            'status' => Ticket::STATUS_RESOLVED,
            'closed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('tickets.close', $ticket));

        $response->assertRedirect(route('tickets.show', $ticket, absolute: false));
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_CLOSED,
        ]);
        $this->assertNotNull($ticket->fresh()->closed_at);
    }

    public function test_employee_cannot_close_own_open_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $user->id,
            'status' => Ticket::STATUS_OPEN,
            'closed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('tickets.show', $ticket))
            ->patch(route('tickets.close', $ticket));

        $response
            ->assertRedirect(route('tickets.show', $ticket, absolute: false))
            ->assertSessionHasErrors('status');
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_OPEN,
            'closed_at' => null,
        ]);
    }

    public function test_employee_cannot_close_another_users_resolved_ticket(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $otherUser->id,
            'status' => Ticket::STATUS_RESOLVED,
            'closed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('tickets.close', $ticket));

        $response->assertForbidden();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_RESOLVED,
            'closed_at' => null,
        ]);
    }
}
