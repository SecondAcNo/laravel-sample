<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketDataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_relationships_are_available(): void
    {
        $requester = User::factory()->create();
        $agent = User::factory()->supportAgent()->create();
        $category = Category::factory()->create();

        $ticket = Ticket::factory()->create([
            'category_id' => $category->id,
            'requester_id' => $requester->id,
            'assignee_id' => $agent->id,
        ]);

        $comment = TicketComment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $requester->id,
        ]);

        $this->assertTrue($ticket->category->is($category));
        $this->assertTrue($ticket->requester->is($requester));
        $this->assertTrue($ticket->assignee->is($agent));
        $this->assertTrue($ticket->comments->first()->is($comment));
        $this->assertTrue($comment->ticket->is($ticket));
        $this->assertTrue($comment->user->is($requester));
    }

    public function test_default_ticket_seed_data_is_created(): void
    {
        $this->seed();

        $this->assertDatabaseHas('categories', [
            'name' => 'Accounts and Access',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('tickets', [
            'ticket_no' => 'TKT-20260504-0001',
            'type' => Ticket::TYPE_ACCESS_REQUEST,
            'status' => Ticket::STATUS_OPEN,
            'priority' => Ticket::PRIORITY_NORMAL,
        ]);

        $this->assertDatabaseHas('ticket_comments', [
            'body' => 'I need access for the deployment documentation update.',
        ]);
    }
}
