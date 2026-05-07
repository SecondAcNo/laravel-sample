<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketAttachment>
 */
class TicketAttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'uploaded_by' => User::factory(),
            'original_name' => 'document.txt',
            'stored_path' => 'ticket_attachments/document.txt',
            'mime_type' => 'text/plain',
            'size' => 128,
        ];
    }
}
