<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\AuditLogService;
use App\Services\TicketStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketStatusController extends Controller
{
    public function close(Request $request, Ticket $ticket, TicketStatusService $ticketStatusService, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('close', $ticket);

        $beforeValues = $ticket->only(['status', 'closed_at']);
        $ticketStatusService->transition($ticket, $request->user(), Ticket::STATUS_CLOSED);
        $auditLogService->record($request, 'status changed', $ticket, $beforeValues, $ticket->only([
            'status',
            'closed_at',
        ]));

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket closed.');
    }
}
