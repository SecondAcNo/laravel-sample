<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_upload_attachment_to_own_ticket(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('tickets.attachments.store', $ticket), [
                'attachment' => UploadedFile::fake()->create('vpn-error.txt', 4, 'text/plain'),
            ]);

        $response->assertRedirect(route('tickets.show', $ticket, absolute: false));

        $attachment = TicketAttachment::query()->firstOrFail();

        Storage::disk('local')->assertExists($attachment->stored_path);
        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'uploaded_by' => $user->id,
            'original_name' => 'vpn-error.txt',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'attachment added',
            'target_type' => TicketAttachment::class,
            'target_id' => $attachment->id,
        ]);
    }

    public function test_employee_cannot_upload_attachment_to_another_users_ticket(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('tickets.attachments.store', $ticket), [
                'attachment' => UploadedFile::fake()->create('blocked.txt', 1, 'text/plain'),
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    public function test_support_agent_can_upload_attachment_to_assigned_ticket(): void
    {
        Storage::fake('local');

        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();

        $response = $this
            ->actingAs($agent)
            ->post(route('tickets.attachments.store', $ticket), [
                'attachment' => UploadedFile::fake()->create('diagnostics.txt', 2, 'text/plain'),
            ]);

        $response->assertRedirect(route('agent.tickets.show', $ticket, absolute: false));
        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'uploaded_by' => $agent->id,
            'original_name' => 'diagnostics.txt',
        ]);
    }

    public function test_user_can_download_attachment_from_viewable_ticket(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $user->id,
        ]);
        Storage::disk('local')->put('ticket_attachments/test.txt', 'hello');
        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'uploaded_by' => $user->id,
            'original_name' => 'test.txt',
            'stored_path' => 'ticket_attachments/test.txt',
            'mime_type' => 'text/plain',
            'size' => 5,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('ticket-attachments.download', $attachment));

        $response->assertOk();
    }

    public function test_user_cannot_download_attachment_from_unviewable_ticket(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();
        Storage::disk('local')->put('ticket_attachments/test.txt', 'hello');
        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'stored_path' => 'ticket_attachments/test.txt',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('ticket-attachments.download', $attachment));

        $response->assertForbidden();
    }
}
