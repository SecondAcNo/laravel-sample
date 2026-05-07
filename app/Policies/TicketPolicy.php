<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['employee', 'support_agent', 'admin'], true);
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin()
            || $ticket->requester_id === $user->id
            || ($user->isSupportAgent() && $ticket->assignee_id === $user->id);
    }

    public function viewAssigned(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin()
            || ($user->isSupportAgent() && (
                $ticket->assignee_id === $user->id
                || $ticket->assignee_id === null
            ));
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function attach(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function downloadAttachment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket)
            || $this->viewAssigned($user, $ticket);
    }

    public function updateStatus(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin()
            || ($user->isSupportAgent() && $ticket->assignee_id === $user->id);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin()
            || (
                $ticket->requester_id === $user->id
                && $ticket->status === Ticket::STATUS_OPEN
            );
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function claim(User $user, Ticket $ticket): bool
    {
        return $user->isSupportAgent()
            && $ticket->assignee_id === null;
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $ticket->requester_id === $user->id;
    }
}
