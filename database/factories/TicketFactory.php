<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_no' => 'TKT-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(1000, 9999),
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement([
                Ticket::TYPE_ACCESS_REQUEST,
                Ticket::TYPE_INCIDENT,
                Ticket::TYPE_GENERAL_REQUEST,
            ]),
            'status' => Ticket::STATUS_OPEN,
            'priority' => fake()->randomElement([
                Ticket::PRIORITY_LOW,
                Ticket::PRIORITY_NORMAL,
                Ticket::PRIORITY_HIGH,
                Ticket::PRIORITY_URGENT,
            ]),
            'category_id' => Category::factory(),
            'requester_id' => User::factory(),
            'assignee_id' => null,
            'closed_at' => null,
        ];
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'assignee_id' => $user->id,
        ]);
    }
}
