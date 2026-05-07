<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TicketStatusService
{
    /**
     * @return array<int, string>
     */
    public function availableStatusesFor(Ticket $ticket, User $user): array
    {
        if ($user->isAdmin()) {
            return [
                Ticket::STATUS_OPEN,
                Ticket::STATUS_TRIAGED,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_WAITING_USER,
                Ticket::STATUS_RESOLVED,
                Ticket::STATUS_CLOSED,
            ];
        }

        if ($user->isSupportAgent() && $ticket->assignee_id === $user->id) {
            return $this->supportAgentTransitions()[$ticket->status] ?? [];
        }

        if ($ticket->requester_id === $user->id && $ticket->status === Ticket::STATUS_RESOLVED) {
            return [Ticket::STATUS_CLOSED];
        }

        return [];
    }

    public function transition(Ticket $ticket, User $user, string $nextStatus): Ticket
    {
        if (! in_array($nextStatus, $this->availableStatusesFor($ticket, $user), true)) {
            throw ValidationException::withMessages([
                'status' => 'The selected status transition is not allowed.',
            ]);
        }

        $ticket->forceFill([
            'status' => $nextStatus,
            'closed_at' => $nextStatus === Ticket::STATUS_CLOSED ? now() : null,
        ])->save();

        return $ticket;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function supportAgentTransitions(): array
    {
        return [
            Ticket::STATUS_OPEN => [Ticket::STATUS_TRIAGED],
            Ticket::STATUS_TRIAGED => [Ticket::STATUS_IN_PROGRESS],
            Ticket::STATUS_IN_PROGRESS => [
                Ticket::STATUS_WAITING_USER,
                Ticket::STATUS_RESOLVED,
            ],
            Ticket::STATUS_WAITING_USER => [Ticket::STATUS_IN_PROGRESS],
        ];
    }
}
