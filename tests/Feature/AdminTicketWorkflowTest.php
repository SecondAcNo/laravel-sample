<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->supportAgent()->create([
            'name' => 'Agent Summary User',
        ]);
        $category = Category::factory()->create([
            'name' => 'Network Summary',
        ]);
        Ticket::factory()->assignedTo($agent)->create([
            'category_id' => $category->id,
            'priority' => Ticket::PRIORITY_URGENT,
            'status' => Ticket::STATUS_OPEN,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.dashboard'));

        $response
            ->assertOk()
            ->assertSee('管理ダッシュボード')
            ->assertSee('担当者別チケット')
            ->assertSee('カテゴリ別チケット')
            ->assertSee('Agent Summary User')
            ->assertSee('Network Summary');
    }

    public function test_employee_cannot_view_admin_dashboard(): void
    {
        $employee = User::factory()->create();

        $response = $this
            ->actingAs($employee)
            ->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_support_agent_cannot_view_admin_dashboard(): void
    {
        $agent = User::factory()->supportAgent()->create();

        $response = $this
            ->actingAs($agent)
            ->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_all_tickets(): void
    {
        $admin = User::factory()->admin()->create();
        $employeeTicket = Ticket::factory()->create([
            'title' => 'Employee ticket',
        ]);
        $otherTicket = Ticket::factory()->create([
            'title' => 'Other ticket',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.tickets.index'));

        $response
            ->assertOk()
            ->assertSee($employeeTicket->title)
            ->assertSee($otherTicket->title);
    }

    public function test_admin_can_search_all_tickets(): void
    {
        $admin = User::factory()->admin()->create();
        $matchingTicket = Ticket::factory()->create([
            'title' => 'AWS IAM request',
            'status' => Ticket::STATUS_OPEN,
        ]);
        Ticket::factory()->create([
            'title' => 'Printer issue',
            'status' => Ticket::STATUS_RESOLVED,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.tickets.index', [
                'q' => 'IAM',
                'status' => Ticket::STATUS_OPEN,
            ]));

        $response
            ->assertOk()
            ->assertSee($matchingTicket->title)
            ->assertDontSee('Printer issue');
    }

    public function test_admin_can_view_any_ticket_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.tickets.show', $ticket));

        $response
            ->assertOk()
            ->assertSee($ticket->ticket_no)
            ->assertSee($ticket->title);
    }

    public function test_employee_cannot_view_admin_ticket_index(): void
    {
        $employee = User::factory()->create();

        $response = $this
            ->actingAs($employee)
            ->get(route('admin.tickets.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_assign_support_agent_to_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->create([
            'assignee_id' => null,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.tickets.assignee.update', $ticket), [
                'assignee_id' => $agent->id,
            ]);

        $response
            ->assertRedirect(route('admin.tickets.show', $ticket))
            ->assertSessionHas('status', 'Ticket assignee updated.');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => $agent->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'ticket assignee changed',
            'target_type' => Ticket::class,
            'target_id' => $ticket->id,
        ]);

        $auditLog = AuditLog::query()->where('target_id', $ticket->id)->firstOrFail();

        $this->assertSame(['assignee_id' => null], $auditLog->before_values);
        $this->assertSame(['assignee_id' => $agent->id], $auditLog->after_values);
    }

    public function test_admin_can_unassign_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->create([
            'assignee_id' => $agent->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.tickets.assignee.update', $ticket), [
                'assignee_id' => '',
            ]);

        $response->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => null,
        ]);
    }

    public function test_employee_cannot_assign_ticket(): void
    {
        $employee = User::factory()->create();
        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->create();

        $response = $this
            ->actingAs($employee)
            ->patch(route('admin.tickets.assignee.update', $ticket), [
                'assignee_id' => $agent->id,
            ]);

        $response->assertForbidden();
    }

    public function test_admin_cannot_assign_employee_as_support_agent(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'assignee_id' => null,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.tickets.show', $ticket))
            ->patch(route('admin.tickets.assignee.update', $ticket), [
                'assignee_id' => $employee->id,
            ]);

        $response
            ->assertRedirect(route('admin.tickets.show', $ticket))
            ->assertSessionHasErrors('assignee_id');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => null,
        ]);
    }

    public function test_admin_can_update_ticket_status_to_any_status(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create([
            'status' => Ticket::STATUS_OPEN,
            'closed_at' => null,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.tickets.status.update', $ticket), [
                'status' => Ticket::STATUS_CLOSED,
            ]);

        $response
            ->assertRedirect(route('admin.tickets.show', $ticket))
            ->assertSessionHas('status', 'Ticket status updated.');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_CLOSED,
        ]);
        $this->assertNotNull($ticket->fresh()->closed_at);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'status changed',
            'target_type' => Ticket::class,
            'target_id' => $ticket->id,
        ]);

        $auditLog = AuditLog::query()->where('target_id', $ticket->id)->firstOrFail();

        $this->assertSame(Ticket::STATUS_OPEN, $auditLog->before_values['status']);
        $this->assertSame(Ticket::STATUS_CLOSED, $auditLog->after_values['status']);
    }

    public function test_employee_cannot_update_ticket_status_through_admin_route(): void
    {
        $employee = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'status' => Ticket::STATUS_OPEN,
        ]);

        $response = $this
            ->actingAs($employee)
            ->patch(route('admin.tickets.status.update', $ticket), [
                'status' => Ticket::STATUS_CLOSED,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
    }

    public function test_admin_can_comment_on_any_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('tickets.comments.store', $ticket), [
                'body' => 'Admin note for this ticket.',
            ]);

        $response
            ->assertRedirect(route('admin.tickets.show', $ticket, absolute: false))
            ->assertSessionHas('status', 'Comment added.');

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'body' => 'Admin note for this ticket.',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'comment added',
        ]);
    }

    public function test_admin_can_upload_attachment_to_any_ticket(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('tickets.attachments.store', $ticket), [
                'attachment' => UploadedFile::fake()->create('admin-note.txt', 3, 'text/plain'),
            ]);

        $response
            ->assertRedirect(route('admin.tickets.show', $ticket, absolute: false))
            ->assertSessionHas('status', 'Attachment uploaded.');

        $attachment = TicketAttachment::query()->firstOrFail();

        Storage::disk('local')->assertExists($attachment->stored_path);
        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'uploaded_by' => $admin->id,
            'original_name' => 'admin-note.txt',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'attachment added',
            'target_type' => TicketAttachment::class,
            'target_id' => $attachment->id,
        ]);
    }
}
